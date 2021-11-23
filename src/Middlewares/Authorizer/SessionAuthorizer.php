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

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$isAuthorized = false;

		// Check a session varaiable existence to determine whether user is logged in.
		// This session variable is set in LoginAuthenticator.
		$spec = $this->loader->getAppInfo("spec");
		$rootName = $spec["options"]["session"]["name"] ?? "";
		if (isset($_SESSION[$rootName]))
		{
			$isAuthorized = true;
		}

		if (!$isAuthorized)
		{
			$this->loader->getService("loggerManager")->alert("Not authorized: method = {httpmethod}, resource = {resource}", [
				"method"=>__METHOD__,
				"httpmethod"=>$request->getMethod(),
				"resource"=>$this->loader->getRouteInfo("args")["resource"]
			]);

			throw new HttpException(HttpException::ERRNO_PARAMETER_NOTAUTHORIZED, HttpException::ERRMSG_PARAMETER_NOTAUTHORIZED);
		}

		return;

	}

}
