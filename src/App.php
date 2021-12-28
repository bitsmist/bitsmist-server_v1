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

		// Init error handling
		$this->initError();

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
			$request = $this->container["request"];
			$request = $request->withAttribute("app", $this);
			$request = $request->withAttribute("container", $this->container);
			$this->container["services"]["setupController"]->dispatch($request);

			// Dispatch main middleware chain
			$request = $this->container["services"]["setupController"]->getRequest();
			$request = $request->withAttribute("app", null); // Remove access to app
			$request = $request->withAttribute("container", null); // Remove access to container
			$request = $request->withAttribute("resultCode", HttpException::ERRNO_NONE);
			$request = $request->withAttribute("resultMessage", HttpException::ERRMSG_NONE);
			$request = $request->withAttribute("services", $this->container["services"]);
			$request = $request->withAttribute("settings", $this->container["settings"]);
			$response = $this->container["services"]["mainController"]->dispatch($request);

			// Emit
			$this->container["services"]["emitter"]->emit($response);
		}
		catch (\Throwable $e)
		{
			$this->exception = $e;
			throw $e;
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

		return Util::resolveInstance($options, $this->container);

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
	 * Init error handling.
	 */
	private function initError()
	{

		register_shutdown_function(function () {
			$e = error_get_last();
			$type = $e["type"] ?? null;
			if( $type == E_ERROR || $type == E_PARSE || $type == E_CORE_ERROR || $type == E_COMPILE_ERROR || $type == E_USER_ERROR )
			{
				// Default response code
				// Can be modified in error handling middlewares
				http_response_code(500);

				if (!$this->exception)
				{
					$this->exception = new \ErrorException($e["message"], 0, $e["type"], $e["file"], $e["line"]);
				}

				try
				{
					// Dispatch error middleware chain
					$request = $this->container["services"]["mainController"]->getRequest() ?? $this->container["request"];
					$request = $request->withAttribute("exception", $this->exception);
					$request = $request->withAttribute("services", $this->container["services"]);
					$request = $request->withAttribute("settings", $this->container["settings"]);
					$response = $this->container["services"]["errorController"]->dispatch($request);
					$this->container["services"]["emitter"]->emit($response);
				}
				catch (\Throwable $ex)
				{
					if (ini_get("display_errors"))
					{
						$this->showError($ex);
					}
				}
			}
		});
	}

	// -------------------------------------------------------------------------

	/**
	 * Show error.
	 *
	 * @param	$ex				Exception to show.
	 */
	private function showError($ex)
	{

		if (ini_get("html_errors"))
		{
			echo "<b>Fatal error</b>: " . $ex->getMessage() . " in <b>" . $ex->getFile() . "</b> on line <b>". $ex->getLine() . "</b><br>";
		}
		else
		{
			echo "Fatal error: " . $ex->getMessage() . " in " . $ex->getFile() . " on line ". $ex->getLine();
		}

	}

}
