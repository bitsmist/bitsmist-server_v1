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
use Bitsmist\v1\Middlewares\Handler\CustomRequestHandler;
use Closure;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Custom response handler class
// =============================================================================

class CustomResponseHandler extends CustomRequestHandler
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		// Call middleware chain
		$response = $handler->handle($request);

		// Get handlers
		$files = $request->getAttribute("vars")->replace($this->getOption("uses"));
		$handlers = $this->getHandlers($files);

		// Handle response
		return $this->execHandlers($handlers, $response);

	}

}
