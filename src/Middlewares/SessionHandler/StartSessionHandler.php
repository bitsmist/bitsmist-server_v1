<?php
// =============================================================================
/**
 * Bitsmist - PHP WebAPI Server Framework
 * Bitsmist Server - PHP WebAPI Server Framework
 *
 * @copyright		Masaki Yasutake
 * @link			https://bitsmist.com/
 * @license			https://github.com/bitsmist/bitsmist/blob/master/LICENSE
 */
// =============================================================================

namespace Bitsmist\v1\Middlewares\SessionHandler;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Start session handler class
// =============================================================================

class StartSessionHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		if (session_status() != PHP_SESSION_ACTIVE)
		{
			// Set session name
			$cookieName = $request->getAttribute("settings")["options"]["session"]["cookieName"] ?? "";
			if ($cookieName)
			{
				session_name($cookieName);
			}

			// Set cookie options
			$cookieOptions = $request->getAttribute("settings")["options"]["session"]["cookieOptions"] ?? null;
			if ($cookieOptions)
			{
				session_set_cookie_params($cookieOptions);
			}

			// Start session
			if (!session_start())
			{
				throw new \RuntimeException("session_start() failed.");
			}
		}

		// Overwrite options
		setcookie(session_name(), session_id(), $cookieOptions);

		return $handler->handle($request);
	}

}
