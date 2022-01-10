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

namespace Bitsmist\v1\Middlewares\Validator;

use Bitsmist\v1\Exceptions\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Origin header validator class
// =============================================================================

class OriginHeaderValidator extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * @throws HttpException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$originHeader = $request->getHeaders()["origin"][0] ?? "";
		$allowedOrigins = $request->getAttribute("settings")["options"]["allowedOrigins"] ?? array();

		// Check if origin is in allowed origins list
		if ($originHeader && !in_array($originHeader, $allowedOrigins))
		{
			$msg = sprintf("Invalid origin. origin=%s", $originHeader);

			$request->getAttribute("services")["logger"]->alert("{msg}", ["method" => __METHOD__, "msg" => $msg]);

			$e = new HttpException(HttpException::ERRMSG_PARAMETER, HttpException::ERRNO_PARAMETER);
			$e->setDetailMessage($msg);
			throw $e;
		}

		return $handler->handle($request);

	}

}
