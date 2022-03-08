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

namespace Bitsmist\v1\Middlewares\Handler;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Utils\Util;
use Closure;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Custom middleware handler class
// =============================================================================

class CustomMiddlewareHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Middlewares.
	 *
	 * @var		array
	 */
	protected $handlers = array();

	/**
	 * Next handler.
	 *
	 * @var		callable
	 */
	protected $nextHandler = null;

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$this->nextHandler = $handler;

		// Get custom handlers
		$files = $request->getAttribute("vars")->replace($this->getOption("uses"));
		$this->handlers = $this->getHandlers($files);

		reset($this->handlers);
		return $this->handle($request);

	}

	// -------------------------------------------------------------------------

	/**
	 * Handle a request.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Response.
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{

		// Get next custom middleware
		$middleware = current($this->handlers);
		next($this->handlers);

		if (is_callable($middleware))
		{
			// Execute
			$ret = $middleware($request, $this);
		}
		else
		{
			// Call middleware chain
			$ret = $this->nextHandler->handle($request);
		}

		return $ret;

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
  	 * Load handler files.
	 *
	 * @param	$files			Handler file names.
	 *
	 * @return	array
     */
	protected function getHandlers($files): array
	{

		$handlers = array();

		foreach ((array)$files as $fileName)
		{
			if (file_exists($fileName))
			{
				$handlers[] = require $fileName;
			}
		}

		return $handlers;

	}

}
