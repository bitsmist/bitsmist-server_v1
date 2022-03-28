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

		$cookieOptions = $request->getAttribute("settings")["options"]["session"]["cookieOptions"] ?? null;
		$expires = "";

		if (session_status() != PHP_SESSION_ACTIVE)
		{
			// Set session name
			$cookieName = $request->getAttribute("settings")["options"]["session"]["cookieName"] ?? "";
			if ($cookieName)
			{
				session_name($cookieName);
			}

			// Set cookie options
			if ($cookieOptions)
			{
				// Save "expires" option and remove from cookie options.
				$expires = $cookieOptions["expires"] ?? "";
				unset($cookieOptions["expires"]);

				session_set_cookie_params($cookieOptions);
			}

			// Start session
			if (!session_start())
			{
				throw new \RuntimeException("session_start() failed.");
			}
		}

		// Convert "lifetime" to "expires"
		if (array_key_exists("lifetime", $cookieOptions))
		{
			$expires = ( $expires ? $expires : time() + $cookieOptions["lifetime"] );
			unset($cookieOptions["lifetime"]);
		}

		if ($expires)
		{
			$cookieOptions["expires"] = $expires;
		}

		// Overwrite options
		setcookie(session_name(), session_id(), $cookieOptions);

		return $handler->handle($request);
	}

}
