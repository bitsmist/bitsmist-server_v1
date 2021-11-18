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
	 * Loader.
	 *
	 * @var		Loader
	 */
	private $loader = null;

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

		// Init a loader
		$options = $settings["loader"]; //@@@
		$className = $options["className"]; //@@@
		$this->loader = new $className($settings);

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
			$a = 1/0;
			$response = $this->loader->getService("controllerManager")->handle($this->loader->getRequest(), $this->loader->getResponse()); //@@@
		}
		catch (\Throwable $e)
		{
			$exception = $e;
			$response = $this->loader->getService("errorManager")->handle($this->loader->getRequest()->withAttribute("exception", $e), $this->loader->getResponse()); //@@@
		}

		// Send response
		$this->loader->getService("emitterManager")->emit($response); //@@@

		// Re-throw the exception during middleware handling to show errors on screen
		if ($exception)
		{
			throw $exception;
		}

	}

}
