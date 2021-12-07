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
//	Session authorizer class
// =============================================================================

class SessionAuthorizer extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$logger = $request->getAttribute("services")["logger"];
		$isAuthorized = false;

		// Check a session varaiable existence to determine whether user is logged in.
		// This session variable is set in LoginAuthenticator.
		$rootName = $request->getAttribute("settings")["options"]["session"]["name"] ?? "";
		if (isset($_SESSION[$rootName]))
		{
			$isAuthorized = true;
		}

		if (!$isAuthorized)
		{
			$logger->alert("Not authorized", [ "method"=>__METHOD__]);

			throw new HttpException(HttpException::ERRNO_PARAMETER_NOTAUTHORIZED, HttpException::ERRMSG_PARAMETER_NOTAUTHORIZED);
		}

		return $handler->handle($request);

	}

}
