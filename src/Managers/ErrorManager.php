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

namespace Bitsmist\v1\Managers;

use Bitsmist\v1\Managers\MiddlewareManager;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Error manager class.
 */
class ErrorManager extends MiddlewareManager
{

	// -------------------------------------------------------------------------
	//	Constructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	$container		Container.
	 * @param	$options		Options.
	 */
	public function __construct($container, array $options = null)
	{

		// super
		parent::__construct($container, $options);

		// Init error handling
		$this->initError();

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Handle an error.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Response.
	 */
	public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{

		// Rethrow an exeption when no error handler available
		if (count($this->services) == 0)
		{
			throw $request->getAttribute("exception");
		}

		// Process through middlewares
		list($request, $response) = $this->process($request, $response);

		return $response;

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
			if ($this->container["settings"]["options"]["showErrors"] ?? false)
			{
				$e = error_get_last();
				if ($e)
				{
					$type = $e["type"] ?? null;
					if( $type == E_ERROR || $type == E_PARSE || $type == E_CORE_ERROR || $type == E_COMPILE_ERROR || $type == E_USER_ERROR )
					{
						$msg = str_replace('Stack trace:', '<br>Stack trace:', $e['message']);
						$msg = str_replace('#', '<br>#', $msg);
						/*
						echo "<br>";
						echo "Error type:\t {$e['type']}<br>";
						echo "Error message:\t {$msg}<br>";
						echo "Error file:\t {$e['file']}<br>";
						echo "Error line:\t {$e['line']}<br>";
						*/
						echo "<table>";
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
