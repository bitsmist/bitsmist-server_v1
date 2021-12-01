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

namespace Bitsmist\v1\Middlewares\ExceptionHandler;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Echo exception handler class
// =============================================================================

class EchoExceptionHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$exception = $request->getAttribute("exception");

//		echo "Error code:\t {$exception->getCode()}<br>";
		echo "Error message:\t {$exception->getMessage()}<br>";
		echo "Error file:\t {$exception->getFile()}<br>";
		echo "Error lineno:\t {$exception->getLine()}<br>";
		echo "Error trace:\t {$exception->getTraceAsString()}<br>";

		return $handler->handle($request);

	}

}
