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

namespace Bitsmist\v1\Middlewares\Base;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Middleware base trait.
 */
abstract class MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Logger.
	 *
	 * @var		Logger
	 */
	protected $logger = null;

	/**
	 * Options.
	 *
	 * @var		array
	 */
	protected $options = null;

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	options			Middleware options.
	 */
	public function __construct(?array $options = array())
	{

		$this->options = $options;

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Set logger.
	 *
	 * @param	$logger			Logger.
	 */
	public function setLogger($logger)
	{

		$this->logger = $logger;

	}

	// -------------------------------------------------------------------------

	/**
	 * Handle the request.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Request or response.
	 */
	abstract public function __invoke(ServerRequestInterface $request, ResponseInterface $response);
	//public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
		/*
	{
	}
		 */

}

