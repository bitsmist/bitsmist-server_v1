<?php
// =============================================================================
/**
 * Bitsmist - PHP WebAPI Server Framework
 *
 * @copyright		Masaki Yasutake
 * @link			https://bitsmist.com/
 * @license			https://github.com/bitsmist/bitsmist/blob/master/LICENSE
 */
// =============================================================================

namespace Bitsmist\v1;

use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Application class.
 */
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
	private $container = null;

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

		// Init error handling
		$this->initError();

		// Init a container
		$container = new Container();
		$this->container = $container;
		$container["app"] = $this;
		$container["settings"] = $settings;
		$container["appInfo"] = null;
		$container["sysInfo"] = null;
		$container["request"] = null;
		$container["response"] = null;

		// Init a loader
		$this->initLoader($container);

		// Load route info
		$args = $this->container["loader"]->loadRoute();

		// Init request & response
		$container["request"] = $container["loader"]->loadRequest();
		$container["response"] = $container["loader"]->loadResponse();

		// Init system information
		$sysInfo = array();
		$sysInfo["version"] = $settings["version"];
		$sysInfo["rootDir"] = $settings["options"]["rootDir"];
		$sysInfo["sitesDir"] = $settings["options"]["sitesDir"];
		$container["sysInfo"] = $sysInfo;

		// Init application information
		$appInfo = array();
		$appInfo["domain"] = $args["appDomain"] ?? $_SERVER["HTTP_HOST"];
		$appInfo["name"] = $args["appName"] ?? $appInfo["domain"];
		$appInfo["version"] = $args["appVersion"] ?? 1;
		$appInfo["lang"] = $args["appLang"] ?? "ja";
		$appInfo["rootDir"] = $sysInfo["sitesDir"] . $appInfo["name"] . "/";
		$appInfo["args"] = $args;
		$container["appInfo"] = $appInfo;
		$appInfo["settings"] = $container["loader"]->loadSettings();
		$appInfo["spec"] = $container["loader"]->loadSpecs();
		unset($container["appInfo"]);
		$container["appInfo"] = $appInfo;

		// Load services
		$container["loader"]->loadServices();

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Start the application.
	 */
	public function run()
	{

		// Handle request
		try
		{
			$ret = $this->container["controllerManager"]->handle($this->container["request"]);
			$response = $ret[count($ret) - 1];
		}
		catch (\Throwable $e)
		{
			$response = $this->handleException($this->container["request"]->withAttribute("exception", $e), $this->container["response"]);
		}

		// Send response
		try
		{
			$this->container["emitterManager"]->emit($response);
		}
		catch (\Throwable $e)
		{
			$response = $this->handleException($this->container["request"]->withAttribute("exception", $e), $this->container["response"]);
		}

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
			if ($this->container["settings"]["options"]["showErrors"] ?? false)
			{
				$e = error_get_last();
				if( $e['type'] == E_ERROR || $e['type'] == E_PARSE || $e['type'] == E_CORE_ERROR || $e['type'] == E_COMPILE_ERROR || $e['type'] == E_USER_ERROR )
				{
			//		echo "Error type:\t {$e['type']}<br>";
					echo "Error message:\t {$e['message']}<br>";
					echo "Error file:\t {$e['file']}<br>";
					echo "Error line:\t {$e['line']}<br>";
				}
			}
		});

	}

	// -------------------------------------------------------------------------

	/**
	 * Init a loader.
	 *
	 * @param	$container		Container.
	 */
	private function initLoader(Container $container)
	{

		$container["loader"] = function($c)
		{
			$options = array(
				"container" => $c,
			);
			$className = $c["settings"]["loader"]["class"];

			return new $className($options);
		};

	}

	// -------------------------------------------------------------------------

	/**
	 * Handle an exception.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Response.
	 */
	private function handleException(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{

		if (count($this->container["exceptionManager"]->getMiddlewares()) == 0)
		{
			// No error handler available
			throw $request->getAttribute("exception");
		}

		return $this->container["exceptionManager"]($request, $response);

	}

}

