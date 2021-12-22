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
use Bitsmist\v1\Middlewares\Handler\CustomHandler;
use Closure;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Custom request handler class
// =============================================================================

class CustomRequestHandler extends CustomHandler
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		// Get handlers
		$files = $this->replaceVars($this->getOption("uses"));
		$handlers = $this->getHandlers($files);

		// Handle request
		$request = $this->execHandlers($handlers, $request);

		// Call middleware chain
		return $handler->handle($request);

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
  	 * Get request/response handlers.
	 *
	 * @param	$files			Handler file names.
	 *
	 * @return	array.
     */
	protected function getHandlers($files)
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
