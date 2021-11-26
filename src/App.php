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
	 * Global settings.
	 *
	 * @var		Global settings
	 */
	private $settings = null;

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

		$this->settings = $settings;

		// Set php.ini from settings
		$this->setIni($settings["phpOptions"] ?? null);

		// Init error handling
		$this->initError();

		// Init a loader
		$options = $settings["loader"];
		$className = $options["className"];
		$this->loader = new $className($settings);

		// Set php.ini from spec
		$this->setIni($this->loader->getAppInfo("spec")["phpOptions"] ?? null);

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
			$response = $this->loader->getService("controller")->handle($this->loader->getRequest(), $this->loader->getResponse());
		}
		catch (\Throwable $e)
		{
			$exception = $e;
			try
			{
				$response = $this->loader->getService("errorController")->handle($this->loader->getRequest()->withAttribute("exception", $e), $this->loader->getResponse());
			}
			catch (\Throwable $e)
			{
				throw $exception; // Throw an original exception
			}
		}

		// Send response
		$this->loader->getService("emitter")->emit($response);

		// Re-throw the exception during middleware handling to return error messages
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

}
