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

		// Init a container
		$container = array();
		$this->container = &$container;
		$container["app"] = $this;
		$container["settings"] = $settings;
		$container["appInfo"] = null;
		$container["sysInfo"] = null;
		$container["request"] = null;
		$container["response"] = null;

		// Init a loader
		$className = $container["settings"]["loader"]["className"];
		$container["loader"] = new $className(array("container"=>&$container));

		// Load route info
		$args = $container["loader"]->loadRoute();

		// Init request & response
		$container["request"] = $container["loader"]->loadRequest();
		$container["response"] = $container["loader"]->loadResponse();

		// Init system information
		$sysInfo = array();
		$sysInfo["version"] = $settings["version"];
		$sysInfo["rootDir"] = $settings["options"]["rootDir"];
		$sysInfo["sitesDir"] = $settings["options"]["sitesDir"];
		$container["sysInfo"] = &$sysInfo;

		// Init application information
		$appInfo = array();
		$appInfo["domain"] = $args["appDomain"] ?? $_SERVER["HTTP_HOST"];
		$appInfo["name"] = $args["appName"] ?? $appInfo["domain"];
		$appInfo["version"] = $args["appVersion"] ?? 1;
		$appInfo["lang"] = $args["appLang"] ?? "ja";
		$appInfo["rootDir"] = $sysInfo["sitesDir"] . $appInfo["name"] . "/";
		$appInfo["args"] = $args;
		$container["appInfo"] = &$appInfo;

		// Load settings
		$appInfo["settings"] = $container["loader"]->loadSettings();
		$appInfo["spec"] = $container["loader"]->loadSpecs();

		// Load managers
		$container["loader"]->loadManagers();

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
			$response = $this->container["controllerManager"]->handle($this->container["request"], $this->container["response"]);
		}
		catch (\Throwable $e)
		{
			$response = $this->container["errorManager"]->handle($this->container["request"]->withAttribute("exception", $e), $this->container["response"]);
		}

		// Send response
		try
		{
			$this->container["emitterManager"]->emit($response);
		}
		catch (\Throwable $e)
		{
			$response = $this->container["errorManager"]->handle($this->container["request"]->withAttribute("exception", $e), $this->container["response"]);
		}

	}

}
