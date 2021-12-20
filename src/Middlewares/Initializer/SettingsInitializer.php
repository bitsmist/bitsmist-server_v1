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
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * List of setting files that are alread imported.
	 *
	 * @var		array
	 */
	protected $doneFiles = array();

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$container = $request->getAttribute("container");
		$settings = $container["settings"];
		$args = $request->getAttribute("routeInfo")["args"];

		// System info
		$sysInfo = array();
		$sysInfo["version"] = $request->getAttribute("app")->getVersion();
		$sysInfo["rootDir"] = rtrim($settings["options"]["rootDir"], "/");
		$sysInfo["sitesDir"] = rtrim($settings["options"]["sitesDir"], "/");

		// App Info
		$appInfo = array();
		$appInfo["domain"] = $args["appDomain"] ?? $_SERVER["HTTP_HOST"];
		$appInfo["name"] = $args["appName"] ?? $appInfo["domain"];
		$appInfo["version"] = $args["appVersion"] ?? 1;
		$appInfo["lang"] = $args["appLang"] ?? "ja";
		$appInfo["rootDir"] = $sysInfo["sitesDir"] . "/" . $appInfo["name"];

		$request = $request->withAttribute("appInfo", $appInfo);
		$request = $request->withAttribute("sysInfo", $sysInfo);

		// Load extra setting files
		$files = $this->replaceVars($request, $this->getOption("settings"));
		$settings = $this->loadSettings($files, $settings);

		// Reload my settings and do it again
		// since settings might be added in the extra setting files
		$this->options = $settings[$this->name];
		$files = $this->replaceVars($request, $this->getOption("settings"));
		$settings = $this->loadSettings($files, $settings);

		$container["settings"] = $settings;

		return $handler->handle($request);

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
  	 * Replace variables in setting file names.
	 *
	 * @param	$request		Request.
	 * @param	$files			Setting file names.
	 *
	 * @return	Replaced file names.
     */
	protected function replaceVars($request, $files)
	{

		$sysInfo = $request->getAttribute("sysInfo");
		$appInfo = $request->getAttribute("appInfo");
		$args = $request->getAttribute("routeInfo")["args"];
		$sysRoot = $sysInfo["rootDir"];
		$appRoot = $appInfo["rootDir"];

		$argKeys = array_map(function($x){return "{" . $x . "}";}, array_keys($args));
		$from = array_merge(["{sysRoot}", "{appRoot}", "{sysVer}", "{appVer}", "{method}"], $argKeys);
		$to = array_merge([$sysRoot, $appRoot, $sysInfo["version"], $appInfo["version"], $request->getMethod()], array_values($args));

		return str_replace($from, $to, $files);

	}

	// -------------------------------------------------------------------------

	/**
  	 * Load setting files and merge them.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Settings.
     */
	protected function loadSettings($files, $curSettings)
	{

		foreach ((array)$files as $fileName)
		{
			if (!array_key_exists($fileName, $this->doneFiles))
			{
				$spec = $this->loadSettingFile($fileName, $curSettings);
				$curSettings = array_replace_recursive($curSettings, $spec);

				$this->doneFiles[$fileName] = true;
			}
		}

		return $curSettings;

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load a setting file.
	 *
	 * @param	string		$path			Path to a setting file.
	 * @param	array		$current		Current merged settings.
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
