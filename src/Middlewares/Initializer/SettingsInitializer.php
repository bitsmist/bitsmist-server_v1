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

		// Load setting files
		$files = $container["vars"]->replace($this->getOption("uses"));
		$container["settings"] = $this->loadSettings($files, $container["settings"]);

		/*
		// Reload my settings and do it again
		// since settings might be added in the extra setting files
		$this->options = $settings[$this->name];
		$files = $container["vars"]->replace($this->getOption("uses"));
		$container["settings"] = $this->loadSettings($files, $container["settings"]);
		 */

//		$container["settings"] = $settings;

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
