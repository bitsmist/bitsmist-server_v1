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

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Util\Util;
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
	 * Container.
	 *
	 * @var		Container
	 */
	protected $container = null;

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
	 * Start the application.
	 */
	public function run()
	{

		$response = null;
		$exception = null;

		// Dispatch initializer middleware chain
		$request = $this->container["request"];
		$request = $request->withAttribute("resultCode", HttpException::ERRNO_NONE);
		$request = $request->withAttribute("resultMessage", HttpException::ERRMSG_NONE);
		$request = $request->withAttribute("container", $this->container);
		$request = $request->withAttribute("services", $this->container["services"]);
		$request = $request->withAttribute("settings", $this->container["settings"]);
		$this->container["services"]["initializeController"]->dispatch($request);

		try
		{
			// Dispatch middleware chain
			$response = $this->container["services"]["controller"]->dispatch($this->container["services"]["initializeController"]->request);
		}
		catch (\Throwable $e)
		{
			$exception = $e;

			// Dispatch error middleware chain
			$request = $this->container["services"]["initializeController"]->request;
			$request = $request->withAttribute("exception", $e);
			$response = $this->container["services"]["errorController"]->dispatch($request);
		}

		// Send response
		$this->container["services"]["emitter"]->emit($response);

		// Re-throw an exception during middleware handling to show error messages
		if ($exception)
		{
			throw $exception;
		}

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
	 * Create services.
	 *
	 * @return	Container containing services.
	 */
	protected function loadServices()
	{

		$services = new Container();

		foreach ((array)$this->container["settings"]["services"]["uses"] as $key => $value)
		{
			if (is_numeric($key))
			{
				// Does not have options
				$title = $value;
				$options= null;
			}
			else
			{
				// Has options
				$title = $key;
				$options = $value;
			}

			$services[$title] = function ($c) use ($title, $options) {
				// Merge settings
				$options = array_merge($this->container["settings"][$title] ?? array(), $options ?? array());

				return Util::resolveInstance($options, $this->container, $options);
			};
		}

		return $services;

	}

    // -------------------------------------------------------------------------

	/**
	 * Create a request object.
	 *
	 * @return	Request.
	 */
	protected function loadRequest(): ServerRequestInterface
	{

		$className = "\Zend\Diactoros\ServerRequestFactory";

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

		return $className::FromGlobals($_SERVER, $_GET, $body, $_COOKIE, $_FILES);

	}

    // -------------------------------------------------------------------------

	/**
	 * Create a response object.
	 *
	 * @return	Response.
	 */
	protected function loadResponse(): ResponseInterface
	{

		$className = "\Zend\Diactoros\Response";

		return new $className();

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
	 * Init error handling.
	 */
	private function initError()
	{

		// Convert an error to the exception
		set_error_handler(function ($severity, $message, $file, $line) {
			if (error_reporting() & $severity) {
				throw new \ErrorException($message, 0, $severity, $file, $line);
			}
		});

		// Handle an uncaught error
		register_shutdown_function(function () {
			$e = error_get_last();
			if ($e)
			{
				$type = $e["type"] ?? null;
				if( $type == E_ERROR || $type == E_PARSE || $type == E_CORE_ERROR || $type == E_COMPILE_ERROR || $type == E_USER_ERROR )
				{
					if ($this->container["settings"]["options"]["showErrors"] ?? false)
					{
						$msg = $e["message"];
						echo "\n\n";
						echo "Error type:\t {$e['type']}\n";
						echo "Error message:\t {$msg}\n";
						echo "Error file:\t {$e['file']}\n";
						echo "Error line:\t {$e['line']}\n";
					}
					if ($this->container["settings"]["options"]["showErrorsInHTML"] ?? false)
					{
						$msg = str_replace('Stack trace:', '<br>Stack trace:', $e['message']);
						$msg = str_replace('#', '<br>#', $msg);
						echo "<br><br><table>";
						echo "<tr><td>Error type</td><td>{$e['type']}</td></tr>";
						echo "<tr><td style='vertical-align:top'>Error message</td><td>{$msg}</td></tr>";
						echo "<tr><td>Error file</td><td>{$e['file']}</td></tr>";
						echo "<tr><td>Error line</td><td>{$e['line']}</td></tr>";
						echo "</table>";
					}
				}
			}
		});

	}

}
