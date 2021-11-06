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
			// Set session name
			$sessionName = $request->getAttribute("appInfo")["settings"]["options"]["session"]["name"] ?? null;
			if ($sessionName)
			{
				session_name($sessionName);
			}

			// Set cookie options
			$cookieOptions = $request->getAttribute("appInfo")["settings"]["options"]["session"]["cookieOptions"] ?? null;
			if ($cookieOptions)
			{
				$currentOptions = session_get_cookie_params();
				$newOptions = array_merge($currentOptions, $cookieOptions);

				if(PHP_VERSION_ID < 70300)
				{
					session_set_cookie_params(
						$newOptions["lifetime"],
						$newOptions["path"],
						$newOptions["domain"],
						$newOptions["secure"],
						$newOptions["httponly"]
					);
				}
				else
				{
					session_set_cookie_params($newOptions);
				}
			}

			// Start session
			session_start();
		}

	}

}
