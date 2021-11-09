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

namespace Bitsmist\v1\Plugins\Loader;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Managers\MiddlewareManager;
use Bitsmist\v1\Managers\PluginManager;
use Bitsmist\v1\Plugins\Base\PluginBase;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Loader class.
 */
class DefaultLoader extends PluginBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Create a request object.
	 *
	 * @return	Request.
	 */
	public function loadRequest(): ServerRequestInterface
	{

		$container = $this->options["container"];
		$className = $container["settings"]["request"]["className"];

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
	public function loadResponse(): ResponseInterface
	{

		$container = $this->options["container"];
		$className = $container["settings"]["response"]["className"];

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
	public function loadRoute(): ?array
	{

		$container = $this->options["container"];
		$routes = $container["settings"]["router"]["routes"];

		$dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use ($routes) {
			foreach ($routes as $routeName => $route)
			{
				$r->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $route["route"], $routeName);
			}
		});

		$uri = $_SERVER["REQUEST_URI"];
		$method = $_SERVER["REQUEST_METHOD"];
		$routeinfo = $dispatcher->dispatch($method, $uri);

		$args = null;
		switch ($routeinfo[0])
		{
		case \FastRoute\Dispatcher::NOT_FOUND:
			header("HTTP/1.1 404 OK\r\n");
			throw new HttpException(HttpException::ERRNO_PARAMETER_INVALIDROUTE, HttpException::ERRMSG_PARAMETER_INVALIDROUTE);
			//exit;
			break;
		case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
			header("HTTP/1.1 405 OK\r\n");
			throw new HttpException(HttpException::ERRNO_PARAMETER_INVALIDMETHOD, HttpException::ERRMSG_PARAMETER_INVALIDMETHOD);
			//exit;
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

    // -------------------------------------------------------------------------

	/**
	 * Load services.
	 */
	function loadServices()
	{

		$container = $this->options["container"];

		// Error handler manager
		$container["exceptionManager"] = function($c)
		{
			$manager = new MiddlewareManager($c);
			$c["loader"]->loadMiddlewares("exceptions", $manager);

			return $manager;
		};

		// Logger manager
		$container["loggerManager"] = function($c)
		{
			$manager = new PluginManager($c);
			$c["loader"]->loadPlugins("loggers", $manager);

			return $manager;

		};

		// Db manager
		$container["dbManager"] = function($c)
		{
			$manager = new PluginManager($c);
			$c["loader"]->loadPlugins("databases", $manager);

			return $manager;
		};

		// Controller manager
		$container["controllerManager"] = function($c)
		{
			$manager = new PluginManager($c);
			$c["loader"]->loadPlugins("controllers", $manager);

			return $manager;
		};

		// Emitter manager
		$container["emitterManager"] = function($c)
		{
			$manager = new PluginManager($c);
			$c["loader"]->loadPlugins("emitters", $manager);

			return $manager;
		};

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
		//$ret = array_merge($globalSettings, $localSettings);
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

		$appInfo = $this->options["container"]["appInfo"];
		$sysInfo = $this->options["container"]["sysInfo"];
		$method = strtolower($this->options["container"]["request"]->getMethod());
		$resource = strtolower($appInfo["args"]["resource"]);

		$spec = array();

		$spec = $this->loadGlobalSpec($sysInfo, $spec, "common");
		$spec = $this->loadLocalSpec($appInfo, $spec, "common");

		$spec = $this->loadGlobalSpec($sysInfo, $spec, $method);
		$spec = $this->loadLocalSpec($appInfo, $spec, $method);

		$spec = $this->loadGlobalSpec($sysInfo, $spec, $resource);
		$spec = $this->loadLocalSpec($appInfo, $spec, $resource);

		$spec = $this->loadGlobalSpec($sysInfo, $spec, $method, $resource);
		$spec = $this->loadLocalSpec($appInfo, $spec, $method, $resource);

		return $spec;

	}

	// -----------------------------------------------------------------------------

	/**
	 * Load middlewares from the settings.
	 *
	 * @param	$categoryName	Middleware category.
	 * @param	$manager		Middleware manager.
	 * @param	$spec			Spec given to the middleware.
	 */
	public function loadMiddlewares(string $categoryName, MiddlewareManager $manager, ?array $spec = null)
	{

		if (!$spec)
		{
			$spec = $this->options["container"]["appInfo"]["spec"];
		}

		$specs = $spec[$categoryName] ?? array();
		foreach ($specs as $middlewareName => $options)
		{
			$this->loadMiddleware($middlewareName, $options, $manager);
		}

	}

	// -----------------------------------------------------------------------------

	/**
	 * Load plugins from the settings.
	 *
	 * @param	$categoryName	Plugin category.
	 * @param	$manager		Plugin manager.
	 * @param	$spec			Spec given to the plugin.
	 */
	public function loadPlugins(string $categoryName, PluginManager $manager, ?array $spec = null)
	{

		if (!$spec)
		{
			$spec = $this->options["container"]["appInfo"]["spec"];
		}

		$specs = $spec[$categoryName] ?? array();
		foreach ($specs as $pluginName => $options)
		{
			$this->loadPlugin($categoryName, $pluginName, $options, $manager);
		}

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

		$request = $this->options["container"]["request"];;
		$method = strtolower($request->getMethod());
		$resource = strtolower($this->options["container"]["appInfo"]["args"]["resource"]);
		$appInfo = $this->options["container"]["appInfo"];

		$ret = null;
		$fileName = $appInfo["rootDir"] . "handlers/" . $method . ($resource ? "_" : "") . $resource . ($eventName ? "_" : "") . $eventName . ".php";
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

		$request = $this->options["container"]["request"];;
		$method = strtolower($request->getMethod());
		$resource = strtolower($this->options["container"]["appInfo"]["args"]["resource"]);
		$appInfo = $this->options["container"]["appInfo"];

		$ret = false;

		$fileName = $appInfo["rootDir"] . "handlers/" . $method . ($resource ? "_" : "") . $resource . ($eventName ? "_" : "") . $eventName . ".php";
		if (is_readable($fileName))
		{
			$ret = true;
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

		$sysInfo = $this->options["container"]["sysInfo"];

		$sysSettings = array();
		$sysSettingFile = $sysInfo["rootDir"] . "conf/v" . $sysInfo["version"] . "/settings.php";
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

		$appInfo = $this->options["container"]["appInfo"];

		$appSettings = array();
		$appSettingFile = $appInfo["rootDir"] . "conf/settings.php";
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
				/*
				$ret = array_merge($spec, $newSpec);
				$ret["middlewares"] = array_merge($spec["middlewares"] ?? array(), $newSpec["middlewares"] ?? array());
				 */
				$ret = $this->mergeArray($spec, $newSpec);
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
				/*
				$ret = array_merge($spec, $newSpec);
				$ret["middlewares"] = array_merge($spec["middlewares"] ?? array(), $newSpec["middlewares"] ?? array());
				 */
				$ret = $this->mergeArray($spec, $newSpec);
				$ret["lastSpecFile"] = $method . ($resource ? "_" : "") . $resource;
			}
		}

		return $ret;

	}

	// -----------------------------------------------------------------------------

	/**
	 * Load a middleware.
	 *
	 * @param	$response		Response.
	 * @param	$options		Options.
	 * @param	$manager		Middleware manager.
	 */
	private function loadMiddleware(string $middlewareName, array $options, MiddlewareManager $manager)
	{

		$settings = $this->options["container"]["appInfo"]["settings"]["middlewares"];

		$setting = $settings[$middlewareName];
		$setting = array_merge($setting, $options);
		//$setting = $this->mergeArray($setting, $options);
		$manager->add($middlewareName, $setting);

	}

	// -----------------------------------------------------------------------------

	/**
	 * Load a Plugin.
	 *
	 * @param	$categoryName	Plugin category.
	 * @param	$pluginName		Plugin name.
	 * @param	$options		Options.
	 * @param	$manager		Plugin manager.
	 */
	private function loadPlugin(string $categoryName, string $pluginName, array $options, PluginManager $manager)
	{

		$settings = $this->options["container"]["appInfo"]["settings"][$categoryName];

		$setting = $settings[$pluginName];
		$setting = array_merge($setting, $options);
		//$setting = $this->mergeArray($setting, $options);
		$manager->add($pluginName, $setting);

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
	private function mergeArray(array $arr1, array $arr2, int $depth = 2): array
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

}

