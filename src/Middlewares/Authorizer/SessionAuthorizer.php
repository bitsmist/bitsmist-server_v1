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

use Bitsmist\v1\Exceptions\HttpException;
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
		$authInfo = null;

		// Check a session varaiable existence to determine whether user is logged in.
		// This session variable is set in LoginAuthenticator.
		$rootName = $request->getAttribute("settings")["options"]["session"]["name"] ?? "authInfo";
		if (isset($_SESSION[$rootName]))
		{
			$isAuthorized = true;
			$authInfo = $_SESSION[$rootName];
		}

		if (!$isAuthorized)
		{
			$logger->alert("Not authorized. method={httpMethod}, resource={resource}", [
				"method" => __METHOD__,
				"httpMethod" => $request->getMethod(),
				"resource" => $request->getAttribute("routeInfo")["args"]["resource"] ?? ""
			]);

			throw new HttpException(HttpException::ERRMSG_PARAMETER_NOTAUTHORIZED, HttpException::ERRNO_PARAMETER_NOTAUTHORIZED);
		}

		$request->withAttribute("authInfo", $authInfo);

		return $handler->handle($request);

	}

}
