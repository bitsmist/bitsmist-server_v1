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

		$this->settings = $settings;
		$this->container = new Container();

		// Set php.ini from settings
		$this->setIni($settings["phpOptions"] ?? null);

		// Init system info
		$sysInfo = array();
		$sysInfo["version"] = $settings["version"];
		$sysInfo["rootDir"] = $settings["options"]["rootDir"];
		$sysInfo["sitesDir"] = $settings["options"]["sitesDir"];
		$sysInfo["settings"] = $settings;
		$this->container["sysInfo"] = $sysInfo;

		// Init route info
		$this->routeInfo = $this->loadRoute($settings["router"]);
		$this->container["routeInfo"] = $this->routeInfo;

		// Init application info
		$appInfo = array();
		$appInfo["domain"] = $args["appDomain"] ?? $_SERVER["HTTP_HOST"];
		$appInfo["name"] = $args["appName"] ?? $appInfo["domain"];
		$appInfo["version"] = $args["appVersion"] ?? 1;
		$appInfo["lang"] = $args["appLang"] ?? "ja";
		$appInfo["rootDir"] = $sysInfo["sitesDir"] . $appInfo["name"] . "/";
		$this->container["appInfo"] = $appInfo;
		$this->container["settings"] = $this->loadSettings();
		$this->container["spec"] = $this->loadSpecs();

		// Set php.ini from spec
		$this->setIni($this->container["spec"]["phpOptions"] ?? null);

		// Init request & response
		$this->container["request"] = $this->loadRequest($this->container["spec"]["request"]);
		$this->container["response"] = $this->loadResponse($this->container["spec"]["response"]);

		// Load services
		$options = $this->container["spec"]["services"]["uses"] ?? null;
		$this->container["services"] = $this->loadServices($options);

		// Controller
		$serviceOptions = $this->container["spec"]["controller"];
		$className = $serviceOptions["className"];
		$this->controller = new $className($this->container, $serviceOptions);

		// ErrorController
		$serviceOptions = $this->container["spec"]["errorController"];
		$className = $serviceOptions["className"];
		$this->errorController = new $className($this->container, $serviceOptions);

		// Emitter
		$serviceOptions = $this->container["spec"]["emitter"];
		$className = $serviceOptions["className"];
		$this->emitter = new $className($this->container, $serviceOptions);
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

		// Handle request
		try
		{
			$response = $this->controller->dispatch($this->container["request"]);
		}
		catch (\Throwable $e)
		{
			$exception = $e;
			$request = $this->container["request"];
			$response = $this->errorController->dispatch($request->withAttribute("exception", $e));
		}

		// Send response
		$this->emitter->emit($response);

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
	 * Set php.ini.
	 *
	 * @param	$options		Options.
	 */
	protected function setIni(?array $options)
	{

		foreach ((array)$options as $key => $value)
		{
			ini_set($key, $value);
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Create a request object.
	 *
	 * @return	Request.
	 */
	protected function loadRequest($options): ServerRequestInterface
	{

		$className = $options["className"];

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
	protected function loadResponse($options): ResponseInterface
	{

		$className = $options["className"];

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
					if ($this->settings["options"]["showErrors"] ?? false)
					{
						$msg = $e["message"];
						echo "\n\n";
						echo "Error type:\t {$e['type']}\n";
						echo "Error message:\t {$msg}\n";
						echo "Error file:\t {$e['file']}\n";
						echo "Error line:\t {$e['line']}\n";
					}
					if ($this->settings["options"]["showErrorsInHTML"] ?? false)
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

    // -------------------------------------------------------------------------

	/**
	 * Set routes and get the selected route info.
	 *
	 * @return	Route info.
	 *
	 * @throws	HttpException
	 */
	protected function loadRoute($options): ?array
	{

		$className = $options["className"] ?? "nikic\FastRoute";
		$routes = $options["routes"];

		$ret = null;
		switch ($className)
		{
		case "nikic\FastRoute":
			$ret = $this->loadRoute_FastRoute($routes);
			break;
		}

		return $ret;

	}

    // -------------------------------------------------------------------------

	/**
	 * Load services.
	 */
	protected function loadServices($options)
	{

		$services = new Container();

		foreach ((array)$options as $serviceName)
		{
			$serviceOptions = $this->container["spec"][$serviceName];
			$className = $serviceOptions["className"];

			$services[$serviceName] = function ($c) use ($className, $serviceOptions) {
				return new $className($this->container, $serviceOptions);
			};
		}

		return $services;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the global and local settings and merge them.
	 *
	 * @return	Settings.
     */
	protected function loadSettings(): ?array
	{

		$ret = null;

		$globalSettings = $this->loadSettingFile($this->container["sysInfo"]["rootDir"] . "conf/v" . $this->container["sysInfo"]["version"] . "/settings.php");
		$localSettings = $this->loadSettingFile($this->container["appInfo"]["rootDir"] . "conf/settings.php");
		$ret = array_replace_recursive($globalSettings, $localSettings);

		return $ret;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load specs and merge them.
	 *
	 * @return	Specs.
     */
	protected function loadSpecs(): ?array
	{

		$sysBaseDir = $this->container["sysInfo"]["rootDir"] . "specs/v" . $this->container["sysInfo"]["version"] . "/";
		$appBaseDir = $this->container["appInfo"]["rootDir"] . "specs/";
		$method = strtolower($_SERVER["REQUEST_METHOD"]);
		$resource = strtolower($this->container["routeInfo"]["args"]["resource"]);

		$spec = $this->loadSettingFile($sysBaseDir . "common.php");
		$curSpec = $spec;
		$spec = $this->loadSettingFile($appBaseDir . "common.php", $curSpec);
		$curSpec = array_replace_recursive($curSpec, $spec);
		$spec = $this->loadSettingFile($sysBaseDir . $method . ".php", $curSpec);
		$curSpec = array_replace_recursive($curSpec, $spec);
		$spec = $this->loadSettingFile($appBaseDir . $method . ".php", $curSpec);
		$curSpec = array_replace_recursive($curSpec, $spec);
		$spec = $this->loadSettingFile($sysBaseDir . $resource . ".php", $curSpec);
		$curSpec = array_replace_recursive($curSpec, $spec);
		$spec = $this->loadSettingFile($appBaseDir . $resource . ".php", $curSpec);
		$curSpec = array_replace_recursive($curSpec, $spec);
		$spec = $this->loadSettingFile($sysBaseDir . $method . "_" . $resource . ".php", $curSpec);
		$curSpec = array_replace_recursive($curSpec, $spec);
		$spec = $this->loadSettingFile($appBaseDir . $method . "_" . $resource . ".php", $curSpec);
		$curSpec = array_replace_recursive($curSpec, $spec);

		return $curSpec;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the spec file and merge to current spec.
	 *
	 * @param	$path			Path to a setting file.
	 *
	 * @return	Settings array.
     */
	protected function loadSettingFile(string $path, ?array &$current = null): array
	{

		if (is_readable($path))
		{
			$settings = require $path;
		}
		else
		{
			$settings = array();
//			throw new Exception("Setting file not found. file = " . $path);
		}

		return $settings;

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
  	 * Load route using nikic/FastRoute.
	 *
	 * @param	$routes			Routes.
	 *
	 * @return	Route arguments.
     */
	private function loadRoute_FastRoute($routes)
	{

		$dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use ($routes) {
			foreach ($routes as $routeName => $route)
			{
				$r->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route["route"], $routeName);
			}
		});

		$routeinfo = $dispatcher->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);

		$args = null;
		switch ($routeinfo[0])
		{
		case \FastRoute\Dispatcher::NOT_FOUND:
			header("HTTP/1.1 404 OK\r\n");
			throw new HttpException(HttpException::ERRNO_PARAMETER_INVALIDROUTE, HttpException::ERRMSG_PARAMETER_INVALIDROUTE);
			break;
		case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
			header("HTTP/1.1 405 OK\r\n");
			throw new HttpException(HttpException::ERRNO_PARAMETER_INVALIDMETHOD, HttpException::ERRMSG_PARAMETER_INVALIDMETHOD);
			break;
		case \FastRoute\Dispatcher::FOUND:
			$routeName = $routeinfo[1];
			if (($routes[$routeName]["handler"] ?? "default") == "reject")
			{
				header("HTTP/1.1 " . ($routes[$routeName]["status"] ?? "404") . " OK\r\n");
				exit;
			}
			$args = $routeinfo[2];
			break;
		}

		return array(
			"name" => $routeName,
			"args" => $args,
		);

	}

}
