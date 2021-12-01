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
			$sessionName = $request->getAttribute("spec")["options"]["session"]["name"] ?? null;
			if ($sessionName)
			{
				session_name($sessionName);
			}

			// Set cookie options
			$cookieOptions = $request->getAttribute("spec")["options"]["session"]["cookieOptions"] ?? null;
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

			//session_set_save_handler($handler, true);

			// Start session
			session_start();

			return $handler->handle($request);
		}

	}

}
