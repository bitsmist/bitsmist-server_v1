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

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Managers\MiddlewareManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Controller manager class
// =============================================================================

class ControllerManager
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

	/**
	 * Options.
	 *
	 * @var		array
	 */
	protected $options = null;

	/**
	 * Middleware managers.
	 *
	 * @var		array
	 */
	private $handlers = array();

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

		$this->loader = $loader;
		$this->options = $options;

		// Load event handlers
		foreach ($options["events"] as $eventName => $spec)
		{
			$this->handlers[$eventName] = new MiddlewareManager($this->loader, $spec);
		}

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Handle an request.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Response.
	 */
	public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{

		$request = $request->withAttribute("resultCode", HttpException::ERRNO_NONE);
		$request = $request->withAttribute("resultMessage", HttpException::ERRMSG_NONE);

		foreach ($this->handlers as $eventName => $manager)
		{
			list($request, $response) = $manager->process($request, $response);
		}

		return $response;

	}

}
