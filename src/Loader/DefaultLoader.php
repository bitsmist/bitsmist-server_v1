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

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Loader class.
 */
//class DefaultLoader extends PluginBase
class DefaultLoader
{

	protected $services = null;
	protected $request = null;
	protected $response = null;
	protected $appInfo = null;
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

		// Init request & response
		$this->request = $this->loadRequest($settings["request"]);
		$this->response = $this->loadResponse($settings["response"]);

		// Init route info
		$args = $this->loadRoute($settings["router"]);

		// Init application information
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

		$this->loadManagers();

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Create a request object.
	 *
	 * @return	Request.
	 */
	public function loadRequest($options): ServerRequestInterface
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

	public function getService($serviceName)
	{

		return $this->services[$serviceName];

	}

	public function getRequest()
	{

		return $this->request;

	}

	public function getResponse()
	{

		return $this->response;

	}

	public function getSysInfo()
	{

		return $this->sysInfo;

	}

	public function getAppInfo()
	{

		return $this->appInfo;

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
	 * Create a response object.
	 *
	 * @return	Response.
	 */
	public function loadResponse($options): ResponseInterface
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
	public function loadRoute($options): ?array
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
	public function loadManagers()
	{

		$spec = $this->appInfo["spec"];

		$this->services = array();

		// Logger manager
		$options = $spec["loggerManager"];
		$className = $options["className"];
		$this->services["loggerManager"] = new $className($this, $options);

		// Error handler manager
		$options = $spec["errorManager"];
		$className = $options["className"];
		$this->services["errorManager"] = new $className($this, $options);

		// Db manager
		$options = $spec["dbManager"];
		$className = $options["className"];
		$this->services["dbManager"] = new $className($this, $options);

		// Controller manager
		$options = $spec["controllerManager"];
		$className = $options["className"];
		$this->services["controllerManager"] = new $className($this, $options);

		// Emitter manager
		$options = $spec["emitterManager"];
		$className = $options["className"];
		$this->services["emitterManager"] = new $className($this, $options);

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the global and local settings and merge them.
	 *
	 * @return	Settings.
     */
	public function loadSettings(): ?array
	{

		$ret = null;

		$globalSettings = $this->loadGlobalSettings();
		$localSettings = $this->loadLocalSettings();
		$ret = $this->mergeArray($globalSettings, $localSettings);

		return $ret;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the global and local specs and merge them.
	 *
	 * @return	Specs.
     */
	public function loadSpecs(): ?array
	{

		$method = strtolower($this->request->getMethod());
		$resource = strtolower($this->appInfo["args"]["resource"]);

		$spec = array();

		$spec = $this->loadGlobalSpec($this->sysInfo, $spec, "common");
		$spec = $this->loadLocalSpec($this->appInfo, $spec, "common");

		$spec = $this->loadGlobalSpec($this->sysInfo, $spec, $method);
		$spec = $this->loadLocalSpec($this->appInfo, $spec, $method);

		$spec = $this->loadGlobalSpec($this->sysInfo, $spec, $resource);
		$spec = $this->loadLocalSpec($this->appInfo, $spec, $resource);

		$spec = $this->loadGlobalSpec($this->sysInfo, $spec, $method, $resource);
		$spec = $this->loadLocalSpec($this->appInfo, $spec, $method, $resource);

		return $spec;

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

		$method = strtolower($this->request->getMethod());
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

		$method = strtolower($this->request->getMethod());
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
	//	Private
	// -------------------------------------------------------------------------

	/**
  	 * Load the global settings.
	 *
	 * @return	Global settings.
     */
	private function loadGlobalSettings(): array
	{

		$sysSettings = array();
		$sysSettingFile = $this->sysInfo["rootDir"] . "conf/v" . $this->sysInfo["version"] . "/settings.php";
		if (is_readable($sysSettingFile))
		{
			$sysSettings = require $sysSettingFile;
		}
		else
		{
			throw new Exception("global setting file not found.");
		}

		return $sysSettings;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the site local settings.
	 *
	 * @return	Local settings.
     */
	private function loadLocalSettings(): array
	{

		$appSettings = array();
		$appSettingFile = $this->appInfo["rootDir"] . "conf/settings.php";
		if (is_readable($appSettingFile))
		{
			$appSettings = require $appSettingFile;
		}
		else
		{
			throw new Exception("local setting file not found. file = " . $appSettingFile);
		}


		return $appSettings;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the spec file and merge to current spec.
	 *
	 * @param	$sysInfo		System information.
	 * @param	$spec			Spec.
	 * @param	$method			Method.
	 * @param	$resource		Resource.
	 *
	 * @return	Specs.
     */
	private function loadGlobalSpec(array $sysInfo, array $spec, string $method, ?string $resource = ""): array
	{

		$ret = $spec;
		$fileName = $sysInfo["rootDir"] . "specs/v" . $sysInfo["version"] . "/" . $method . ($resource ? "_" : "") . $resource . ".php";
		if (file_exists($fileName))
		{
			$newSpec = require $fileName;
			if (is_array($newSpec))
			{
				$ret = $this->mergeArray($spec, $newSpec, 1);
				$ret["lastSpecFile"] = $method . ($resource ? "_" : "") . $resource;
			}
		}

		return $ret;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the spec file and merge to current spec.
	 *
	 * @param	$sysInfo		System information.
	 * @param	$spec			Spec.
	 * @param	$method			Method.
	 * @param	$resource		Resource.
	 *
	 * @return	Specs.
     */
	private function loadLocalSpec(array $appInfo, array $spec, string $method, string $resource = ""): array
	{

		$ret = $spec;
		$fileName = $appInfo["rootDir"] . "specs/" . $method . ($resource ? "_" : "") . $resource . ".php";
		if (file_exists($fileName))
		{
			$newSpec = require $fileName;
			if (is_array($newSpec))
			{
				$ret = $this->mergeArray($spec, $newSpec, 1);
				$ret["lastSpecFile"] = $method . ($resource ? "_" : "") . $resource;
			}
		}

		return $ret;

	}

	// -----------------------------------------------------------------------------

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
