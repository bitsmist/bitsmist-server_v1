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

namespace Bitsmist\v1\Middlewares\Authorizer;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Key authorizer class
// =============================================================================

class KeyAuthorizer extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$logger = $request->getAttribute("services")["logger"];
		$isAuthorized = false;

		/*
		// Check a secret key
		if (key matches)
		{
			$isAuthorized = true;
		}
		 */

		if (!$isAuthorized)
		{
			$logger->alert("Not authorized", ["method"=>__METHOD__]);

			throw new HttpException(HttpException::ERRNO_PARAMETER_NOTAUTHORIZED, HttpException::ERRMSG_PARAMETER_NOTAUTHORIZED);
		}

		return $handler->handle($request);

	}

}
