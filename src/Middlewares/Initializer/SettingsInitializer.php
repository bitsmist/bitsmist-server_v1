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

namespace Bitsmist\v1\Middlewares\Initializer;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Settings initializer class
// =============================================================================

class SettingsInitializer extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$container = $request->getAttribute("container");
		$settings = $request->getAttribute("settings");
		$args = $request->getAttribute("routeInfo")["args"];

		$sysInfo = array();
		$sysInfo["version"] = $settings["version"];
		$sysInfo["rootDir"] = $settings["options"]["rootDir"];
		$sysInfo["sitesDir"] = $settings["options"]["sitesDir"];
		$sysInfo["settings"] = $settings;

		$appInfo = array();
		$appInfo["domain"] = $args["appDomain"] ?? $_SERVER["HTTP_HOST"];
		$appInfo["name"] = $args["appName"] ?? $appInfo["domain"];
		$appInfo["version"] = $args["appVersion"] ?? 1;
		$appInfo["lang"] = $args["appLang"] ?? "ja";
		$appInfo["rootDir"] = $sysInfo["sitesDir"] . $appInfo["name"] . "/";

		$request = $request->withAttribute("appInfo", $appInfo);
		$request = $request->withAttribute("sysInfo", $sysInfo);
		$request = $request->withAttribute("settings", $this->loadSetting($request));
		$request = $request->withAttribute("spec", $this->loadSpec($request));

		$container["settings"] = $request->getAttribute("settings");

		return $handler->handle($request);

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the global and local settings and merge them.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Settings.
     */
	protected function loadSetting($request): ?array
	{

		$sysInfo = $request->getAttribute("sysInfo");
		$appInfo = $request->getAttribute("appInfo");
		$routeInfo = $request->getAttribute("routeInfo");

		$spec = $this->loadSettingFile($sysInfo["rootDir"] . "conf/v" . $sysInfo["version"] . "/settings.php");
		$curSpec = $spec;
		$spec = $this->loadSettingFile($appInfo["rootDir"] . "conf/settings.php");
		$curSpec = array_replace_recursive($curSpec, $spec);

		$sysBaseDir = $sysInfo["rootDir"] . "specs/v" . $sysInfo["version"] . "/";
		$appBaseDir = $appInfo["rootDir"] . "specs/";
		$method = strtolower($_SERVER["REQUEST_METHOD"]);
		$resource = strtolower($routeInfo["args"]["resource"]);

		$spec = $this->loadSettingFile($sysBaseDir . "common.php");
		//$curSpec = $spec;
		$curSpec = array_replace_recursive($curSpec, $spec);
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
  	 * Load specs and merge them.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Specs.
     */
	protected function loadSpec($request): ?array
	{

		$sysInfo = $request->getAttribute("sysInfo");
		$appInfo = $request->getAttribute("appInfo");
		$routeInfo = $request->getAttribute("routeInfo");

		$sysBaseDir = $sysInfo["rootDir"] . "specs/v" . $sysInfo["version"] . "/";
		$appBaseDir = $appInfo["rootDir"] . "specs/";
		$method = strtolower($_SERVER["REQUEST_METHOD"]);
		$resource = strtolower($routeInfo["args"]["resource"]);

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


}
