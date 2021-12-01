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

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Basic exception handler class
// =============================================================================

class BasicExceptionHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$exception = $request->getAttribute("exception");

		switch (get_class($exception))
		{
		case "Bitsmist\\v1\Exception\HttpException":
			$resultCode = $exception->getCode();
			$resultMessage = $exception->getMessage();
			break;
		default:
			$resultCode = HttpException::ERRNO_EXCEPTION;
			$resultMessage = HttpException::ERRMSG_EXCEPTION;
			break;
		}

		$request = $request->withAttribute("resultCode", $resultCode);
		$request = $request->withAttribute("resultMessage", $resultMessage);

		return $handler->handle($request);

	}

}
