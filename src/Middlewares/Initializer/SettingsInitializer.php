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

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Utils\Util;
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
		$sysInfo["rootDir"] = rtrim($settings["options"]["sysRoot"], "/");

		// App Info
		$appInfo = array();
		$appInfo["domain"] = $args["appDomain"] ?? $_SERVER["HTTP_HOST"];
		$appInfo["name"] = $args["appName"] ?? $appInfo["domain"];
		$appInfo["version"] = $args["appVer"] ?? 1;
		$appInfo["lang"] = $args["appLang"] ?? "en";

		// App Info (appRoot)
		Util::$replaceDic = array_merge([
			"sysRoot" => $sysInfo["rootDir"],
			"sysVer" => $sysInfo["version"],
			"appVer" => $appInfo["version"],
			"appName" => $appInfo["name"],
			"method" => strtolower($request->getMethod()),
		], $args);
		$appInfo["rootDir"] = Util::replaceVars($settings["options"]["appRoot"] ?? "{sysRoot}/sites/v{appVer}/{appName}");
		Util::$replaceDic["appRoot"] = $appInfo["rootDir"];

		// check app root
		if (!file_exists($appInfo["rootDir"]))
		{
			throw new \RuntimeException(sprintf("App root dir does not exist. rootDir=%s", $appInfo["rootDir"]));
		}

		$request = $request->withAttribute("appInfo", $appInfo);
		$request = $request->withAttribute("sysInfo", $sysInfo);

		// Load extra setting files
		$files = Util::replaceVars($this->getOption("uses"));
		$settings = $this->loadSettings($files, $settings);

		// Reload my settings and do it again
		// since settings might be added in the extra setting files
		$this->options = $settings[$this->name];
		$files = Util::replaceVars($this->getOption("uses"));
		$settings = $this->loadSettings($files, $settings);

		$container["settings"] = $settings;

		return $handler->handle($request);

	}

	// -------------------------------------------------------------------------
	//	Protected
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
