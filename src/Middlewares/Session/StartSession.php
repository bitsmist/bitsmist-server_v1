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

namespace Bitsmist\v1\Middlewares\Session;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Start session class
// =============================================================================

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
			$sessionName = $this->loader->getAppInfo("spec")["options"]["sessionOptions"]["name"] ?? null;
			if ($sessionName)
			{
				session_name($sessionName);
			}

			// Set cookie options
			$cookieOptions = $this->loader->getAppInfo("spec")["options"]["sessionOptions"]["cookieOptions"] ?? null;
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

			//session_set_save_handler($mysql_sesshandler, true);

			// Start session
			session_start();
		}

	}

}
