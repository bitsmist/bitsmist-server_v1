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

namespace Bitsmist\v1\Middlewares\Exception;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Basic exception handler class.
 */
class BasicExceptionHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
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

		return $request;

	}

}
