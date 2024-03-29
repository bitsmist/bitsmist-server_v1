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

namespace Bitsmist\v1\Middlewares\Authenticator;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Utils\DBGatewayUtil;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	DB Login authenticator class
// =============================================================================

class DBLoginAuthenticator extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$logger = $request->getAttribute("services")["logger"];

		// Handle database
		$db = new DBGatewayUtil($this->options);
		$data = $db->getItems($request);

		if ($db->lastResult["count"] == 1)
		{
			// Found
			$rootName = $request->getAttribute("settings")["options"]["session"]["name"] ?? "authInfo";
			$_SESSION[$rootName] = array();
			$root = &$_SESSION[$rootName];

			// Store info from DB to session variables.
			foreach ($data[0] as $key => $value)
			{
				$root[$key] = $value;
			}

			session_regenerate_id(TRUE);

			$logger->notice("User logged in. user={user}", ["method"=>__METHOD__, "user"=>implode(",",$data[0])]);
		}
		else
		{
			// Not found
			$logger->warning("User not found or password not match. gets={user}", [
				"method"=>__METHOD__,
				"user"=>implode(",", $request->getQueryParams())
			]);
		}

		$request = $request->withAttribute("data", $data);
		$request = $request->withAttribute("resultCount", $db->lastResult["count"]);
		$request = $request->withAttribute("totalCount", $db->lastResult["totalCount"]);

		return $handler->handle($request);

	}

}
