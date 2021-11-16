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

namespace Bitsmist\v1\Middlewares\Base;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Middleware base class
// =============================================================================

abstract class MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Loader.
	 *
	 * @var		Loader
	 */
	protected $loader = null;

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
		$this->loader = $options["loader"] ?? null;
		$this->logger = $options["logger"] ?? null;

	}

	// -------------------------------------------------------------------------
	//	Public
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

}

