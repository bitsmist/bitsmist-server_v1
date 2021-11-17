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

namespace Bitsmist\v1\Loader;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Managers\ControllerManager;
use Bitsmist\v1\Managers\ErrorManager;
use Bitsmist\v1\Managers\MiddlewareManager;
use Bitsmist\v1\Managers\PluginManager;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Default loader class
// =============================================================================

class DefaultLoader
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Services.
	 *
	 * @var		Services
	 * @var		Loader
	 */
	protected $services = null;

	/**
	 * Request object.
	 *
	 * @var		Request
	 */
	protected $request = null;

	/**
	 * Response object.
	 *
	 * @var		Response
	 */
	protected $response = null;

	/**
	 * App info.
	 *
	 * @var		App info
	 */
	protected $appInfo = null;

	/**
	 *System info.
	 *
	 * @var		System info
	 */
	protected $sysInfo = null;

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	$container		Container.
	 * @param	$options		Options.
	 */
	public function __construct($settings)
	{

		// Init system info
		$sysInfo = array();
		$this->sysInfo = &$sysInfo;
		$sysInfo["version"] = $settings["version"];
		$sysInfo["rootDir"] = $settings["options"]["rootDir"];
		$sysInfo["sitesDir"] = $settings["options"]["sitesDir"];
		$sysInfo["settings"] = $settings;

		// Init route info
		$args = $this->loadRoute($settings["router"]);

		// Init application info
		$appInfo = array();
		$this->appInfo = &$appInfo;
		$appInfo["domain"] = $args["appDomain"] ?? $_SERVER["HTTP_HOST"];
		$appInfo["name"] = $args["appName"] ?? $this->appInfo["domain"];
		$appInfo["version"] = $args["appVersion"] ?? 1;
		$appInfo["lang"] = $args["appLang"] ?? "ja";
		$appInfo["rootDir"] = $this->sysInfo["sitesDir"] . $appInfo["name"] . "/";
		$appInfo["args"] = $args;
		$appInfo["settings"] = $this->loadSettings();
		$appInfo["spec"] = $this->loadSpecs();

		// Init request & response
		$this->request = $this->loadRequest($appInfo["spec"]["request"]);
		$this->response = $this->loadResponse($appInfo["spec"]["response"]);

		// Load services
		$this->loadServices($appInfo["spec"]["services"]);

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
  	 * Return a serivce object.
	 *
	 * @param	$serviceName	Service name.
	 *
	 * @return	Service object.
     */
	public function getService($serviceName)
	{

		return $this->services[$serviceName];

	}

	// -------------------------------------------------------------------------

	/**
  	 * Return a request object.
	 *
	 * @return	Request object.
     */
	public function getRequest()
	{

		return $this->request;

	}

	// -------------------------------------------------------------------------

	/**
  	 * Return a response object.
	 *
	 * @return	Response object.
     */
	public function getResponse()
	{

		return $this->response;

	}

	// -------------------------------------------------------------------------

	/**
  	 * Return system info.
	 *
	 * @return	System info.
     */
	public function getSysInfo()
	{

		return $this->sysInfo;

	}

	// -------------------------------------------------------------------------

	/**
  	 * Return application info.
	 *
	 * @return	Application info.
     */
	public function getAppInfo()
	{

		return $this->appInfo;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the request handler according to method, resource and event.
	 *
	 * @param	$eventName		An event name.
	 *
	 * @return	Handler.
     */
	public function loadHandler(?string $eventName = ""): ?callable
	{

		$method = strtolower($_SERVER["REQUEST_METHOD"]);
		$resource = strtolower($this->appInfo["args"]["resource"]);

		$ret = null;
		$fileName = $this->appInfo["rootDir"] . "handlers/" . $method . ($resource ? "_" : "") . $resource . ($eventName ? "_" : "") . $eventName . ".php";
		if (file_exists($fileName))
		{
			$ret  = require $fileName;
		}

		return $ret;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Check whether custom handler exists.
	 *
	 * @param	$appInfo		Application information.
	 * @param	$method			Method.
	 * @param	$resource		Resource.
	 *
	 * @return	Exists or not.
     */
	public function isHandlerExists(?string $eventName = ""): bool
	{

		$method = strtolower($_SERVER["REQUEST_METHOD"]);
		$resource = strtolower($this->appInfo["args"]["resource"]);

		$ret = false;

		$fileName = $this->appInfo["rootDir"] . "handlers/" . $method . ($resource ? "_" : "") . $resource . ($eventName ? "_" : "") . $eventName . ".php";
		if (is_readable($fileName))
		{
			$ret = true;
		}

		return $ret;

	}

	// -----------------------------------------------------------------------------

	/**
	 * Merge two arrays. Overwrites $arr1 with $arr2.
	 *
	 * @param	$arr1			Array1.
	 * @param	$arr2			Array2.
	 *
	 * @return	Mergeed array.
	 */
	public function mergeArray(array $arr1, array $arr2, int $depth = 2): array
	{

		$ret = array();

		// Iterate each keys in arr1
		foreach ($arr1 as $key1 => $val1)
		{
			if (is_array($val1))
			{
				$val2 = $arr2[$key1] ?? null;
				if (!$val2)
				{
					$ret[$key1] = $val1;
				}
				else if (is_array($val2))
				{
					if ($depth == 0)
					{
						$ret[$key1] = $val2;
					}
					else
					{
						$ret[$key1] = $this->mergeArray($val1, $val2, $depth-1);
					}
				}
				else
				{
					$ret[$key1] = $val2;
				}
			}
			else
			{
				$ret[$key1] = $arr2[$key1] ?? $val1;
			}
		}

		// Add keys exists only in arr2
		foreach ($arr2 as $key2 => $val2)
		{
			if (!array_key_exists($key2, $arr1))
			{
				$ret[$key2] = $val2;
			}
		}

		return $ret;

	}

	// -------------------------------------------------------------------------
	//	Protected
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
	protected function loadServices($services)
	{

		$this->services = array();

		foreach ($services as $serviceName)
		{
			$serviceOptions = $this->appInfo["spec"][$serviceName];
			$className = $serviceOptions["className"];
			$this->services[$serviceName] = new $className($this, $serviceOptions);
		}

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

		$globalSettings = $this->loadSettingFile($this->sysInfo["rootDir"] . "conf/v" . $this->sysInfo["version"] . "/settings.php");
		$localSettings = $this->loadSettingFile($this->appInfo["rootDir"] . "conf/settings.php");
		$ret = $this->mergeArray($globalSettings, $localSettings);

		return $ret;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the global and local specs and merge them.
	 *
	 * @return	Specs.
     */
	protected function loadSpecs(): ?array
	{

		$sysBaseDir = $this->sysInfo["rootDir"] . "specs/v" . $this->sysInfo["version"] . "/";
		$appBaseDir = $this->appInfo["rootDir"] . "specs/";
		$method = strtolower($_SERVER["REQUEST_METHOD"]);
		$resource = strtolower($this->appInfo["args"]["resource"]);

		$spec1_1 = $this->loadSettingFile($sysBaseDir . "common.php");
		$spec1_2 = $this->loadSettingFile($appBaseDir . "common.php");
		$spec1 = $this->mergeArray($spec1_1, $spec1_2, 1);

		$spec2_1 = $this->loadSettingFile($sysBaseDir . $method . ".php");
		$spec2_2 = $this->loadSettingFile($appBaseDir . $method . ".php");
		$spec2 = $this->mergeArray($spec2_1, $spec2_2, 1);

		$spec3_1 = $this->loadSettingFile($sysBaseDir . $resource . ".php");
		$spec3_2 = $this->loadSettingFile($appBaseDir . $resource . ".php");
		$spec3 = $this->mergeArray($spec3_1, $spec3_2, 1);

		$spec4_1 = $this->loadSettingFile($sysBaseDir . $method . "_" . $resource . ".php");
		$spec4_2 = $this->loadSettingFile($appBaseDir . $method . "_" . $resource . ".php");
		$spec4 = $this->mergeArray($spec4_1, $spec4_2, 1);

		$specA = $this->mergeArray($spec1, $spec2, 1);
		$specB = $this->mergeArray($spec3, $spec4, 1);

		return $this->mergeArray($specA, $specB, 1);;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the spec file and merge to current spec.
	 *
	 * @param	$path			Path to a setting file.
	 *
	 * @return	Settings array.
     */
	protected function loadSettingFile(string $path): array
	{

		$settings = array();

		if (is_readable($path))
		{
			$settings = require $path;
		}
		/*
		else
		{
			throw new Exception("Setting file not found. file = " . $path);
		}
		 */

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

		return $args;

	}

}
