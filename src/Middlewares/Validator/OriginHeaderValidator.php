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
			$logger = $request->getAttribute("services")["logger"]->alert("Invalid origin. origin={origin}", [
				"method" => __METHOD__,
				"origin" => $originHeader
			]);
			throw new HttpException(HttpException::ERRMSG_PARAMETER, HttpException::ERRNO_PARAMETER);
		}

		return $handler->handle($request);

	}

}
