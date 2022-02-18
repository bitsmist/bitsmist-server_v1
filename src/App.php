<?php
// =============================================================================
/**
 * Bitsmist Server - PHP WebAPI Server Framework
 *
 * @copyright		Masaki Yasutake
 * @link			https://bitsmist.com/
 * @license			https://github.com/bitsmist/bitsmist/blob/master/LICENSE
 */
// =============================================================================

namespace Bitsmist\v1;

use Bitsmist\v1\Exceptions\HttpException;
use Bitsmist\v1\Utils\Util;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Application class
// =============================================================================

class App
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Version number.
	 *
	 * @var		string
	 */
	protected $version = "1";

	/**
	 * Container.
	 *
	 * @var		Container
	 */
	protected $container = null;

	/**
	 * Caught exception.
	 *
	 * @var		Throwable
	 */
	protected $exception = null;

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param	$settings		Settings.
	 */
	public function __construct(array $settings)
	{

		// Set php.ini from settings
		Util::setIni($settings["phpOptions"] ?? null);

		// Init error handler
		$this->initErrorHandler();

		// Initialize container
		$this->container = new Container();
		$this->container["app"] = $this;
		$this->container["settings"] = $settings;
		$this->container["request"] = $this->loadRequest();
		$this->container["response"] = $this->loadResponse();
		$this->container["services"] = $this->loadServices();

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Get application version number.
	 *
	 * @return	string
	 */
	public function getVersion()
	{

		return $this->version;

	}

	// -------------------------------------------------------------------------

	/**
	 * Start the application.
	 */
	public function run()
	{

		try
		{
			// Dispatch initializing middleware chain
			$stage = "initializing middleware chain";
			$request = $this->container["request"];
			$request = $request->withAttribute("app", $this);
			$request = $request->withAttribute("container", $this->container);
			$this->container["services"]["setupController"]->dispatch($request);

			// Dispatch main middleware chain
			$stage = "main middleware chain";
			$request = $this->container["services"]["setupController"]->getRequest();
			$request = $request->withAttribute("app", null); // Remove access to app
			$request = $request->withAttribute("container", null); // Remove access to container
			$request = $request->withAttribute("resultCode", HttpException::ERRNO_NONE);
			$request = $request->withAttribute("resultMessage", HttpException::ERRMSG_NONE);
			$request = $request->withAttribute("services", $this->container["services"]);
			$request = $request->withAttribute("settings", $this->container["settings"]);
			$response = $this->container["services"]["mainController"]->dispatch($request);

			// Emit
			$stage = "emitter";
			$this->container["services"]["emitter"]->emit($response);
		}
		catch (\Throwable $e)
		{
			$this->handleError($e, "Error occured in " . $stage);
		}

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
	 * Create a default service manager.
	 *
	 * @return	Service manager.
	 */
	protected function loadServices()
	{

		$options = $this->container["settings"]["services"] ?? array();

		// Set default class if none is set
		if (!isset($options["className"]) && !isset($options["class"]))
		{
			$options["className"] = "Bitsmist\\v1\Services\ServiceManager";
		}

		return Util::resolveInstance($options, "services", $options, $this->container);

	}

    // -------------------------------------------------------------------------

	/**
	 * Create a default request object.
	 *
	 * @return	Request.
	 */
	protected function loadRequest(): ServerRequestInterface
	{

		$options = $this->container["settings"]["request"] ?? array();

		// Set default class if none is set
		if (!isset($options["className"]))
		{
			$options["className"] = "\Zend\Diactoros\ServerRequestFactory";
		}

		$body = $_POST;
		if (strtolower($_SERVER["REQUEST_METHOD"]) == "put")
		{
			parse_str(file_get_contents('php://input'), $body);
		}

		$contentType = $_SERVER["CONTENT_TYPE"] ?? "";
		switch ($contentType)
		{
		case "application/json":
			$body = json_decode(file_get_contents('php://input'), true);
			break;
		}

		$className = $options["className"];

		return $className::FromGlobals($_SERVER, $_GET, $body, $_COOKIE, $_FILES);

	}

    // -------------------------------------------------------------------------

	/**
	 * Create a default response object.
	 *
	 * @return	Response.
	 */
	protected function loadResponse()
	{

		$options = $this->container["settings"]["response"];

		// Set default class if none is set
		if (!isset($options["className"]) && !isset($options["class"]))
		{
			$options["className"] = "\Zend\Diactoros\Response";
		}

		return Util::resolveInstance($options);

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
	 * Init error handler.
	 */
	private function initErrorHandler()
	{

		register_shutdown_function(function () {
			$e = error_get_last();
			$type = $e["type"] ?? null;
			if( $type == E_ERROR || $type == E_PARSE || $type == E_CORE_ERROR || $type == E_COMPILE_ERROR || $type == E_USER_ERROR )
			{
				$ex = new \ErrorException($e["message"], 0, $e["type"], $e["file"], $e["line"]);
				$this->handleError($ex, "Unhandled error");
			}
		});

	}

	// -------------------------------------------------------------------------

	/**
	 * Handle an error.
	 *
	 * @param	$ex				Exception to handle.
	 * @param	$extraMsg		Extra message to show.
	 */
	private function handleError($ex, $extraMsg = "")
	{

		// Default response code
		// Can be modified in error handling middlewares
		http_response_code(500);

		try
		{
			// Dispatch error middleware chain
			$request = $this->container["services"]["mainController"]->getRequest() ?? $this->container["request"];
			$request = $request->withAttribute("exception", $ex);
			$request = $request->withAttribute("services", $this->container["services"]);
			$request = $request->withAttribute("settings", $this->container["settings"]);
			$response = $this->container["services"]["errorController"]->dispatch($request);
			$this->container["services"]["emitter"]->emit($response);
		}
		catch (\Throwable $ex2)
		{
		}

		// Show errors
		$this->showError($ex, $extraMsg);
		if ($ex2)
		{
			$this->showError($ex2, "Error occured in error middleware chain");
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Show an error.
	 *
	 * @param	$ex				Exception to show.
	 * @param	$extraMsg		Extra message to show.
	 */
	private function showError($ex, $extraMsg = "")
	{

		$msg = "";

		if ($this->container["settings"]["options"]["showErrorsInHtml"] ?? false)
		{
			if ($extraMsg)
			{
				$msg .= "<br>\n<br>\n";
				$msg .= $extraMsg;
			}

			$msg .= "<br>\n<br>\n";
			$msg .= "Message: " . $ex->getMessage() . " in <b>" . $ex->getFile() . "</b> on line <b>". $ex->getLine() . "</b><br>\n";
			if (method_exists($ex, "getDetailMessage") && $ex->getDetailMessage())
			{
				$msg .=	"Detail: " . $ex->getDetailMessage(). "<br>\n";
			}
			$msg .=	"Stacktrace:<br>\n" . str_replace("\n", "<br>\n", $ex->getTraceAsString());
		}
		else if ($this->container["settings"]["options"]["showErrors"] ?? false)
		{
			if ($extraMsg)
			{
				$msg .= "\n\n";
				$msg .= $extraMsg;
			}

			$msg .= "\n\n";
			$msg .= "Message: " . $ex->getMessage() . " in " . $ex->getFile() . " on line ". $ex->getLine() . "\n";
			if (method_exists($ex, "getDetailMessage") && $ex->getDetailMessage())
			{
				$msg .=	"Detail: " . $ex->getDetailMessage(). "\n";
			}
			$msg .= "Stacktrace:\n" . $ex->getTraceAsString();
		}

		echo $msg;

	}

}
