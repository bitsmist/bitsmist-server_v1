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

namespace Bitsmist\v1\Middlewares\Session;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Start session class.
 */
class StartSession extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		if (session_status() != PHP_SESSION_ACTIVE)
		{
			// Cookie options
			$cookieOptions = $request->getAttribute("appInfo")["settings"]["options"]["session"]["cookieOptions"] ?? null;
			if ($cookieOptions)
			{
				session_set_cookie_params($cookieOptions);
			}

			// Session name
			$sessionName = $request->getAttribute("appInfo")["settings"]["options"]["session"]["name"] ?? null;
			if ($sessionName)
			{
				session_name($sessionName);
			}

			// Start session
			session_start();

			// Overwrites existing session cookie options
			if ($cookieOptions)
			{
				setcookie(session_name(), session_id(), $cookieOptions);
			}
		}

	}

}
