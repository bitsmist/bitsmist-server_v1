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

namespace Bitsmist\v1\Services;

use Bitsmist\v1\Services\MiddlewareService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Error controller class.
// =============================================================================

class ErrorControllerService extends MiddlewareService
{

	// -------------------------------------------------------------------------
	//	Constructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	$loader			Loader.
	 * @param	$options		Options.
	 */
	public function __construct($loader, array $options = null)
	{

		// super
		parent::__construct($loader, $options);

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
		if (count($this->plugins) == 0)
		{
			throw $request->getAttribute("exception");
		}

		// Process through middlewares
		list($request, $response) = $this->process($request, $response);

		return $response;

	}

}
