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
use Bitsmist\v1\Util\DBUtil;
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

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		// Handle database
		$db = new DBUtil($this->loader, $this->options);
		$data = $db->getItems($request, $response);

		if ($db->resultCount == 1)
		{
			// Found
			$rootName = $this->loader->getAppInfo("spec")["options"]["session"]["name"] ?? "";
			$root = &$_SESSION;
			if ($rootName)
			{
				$_SESSION[$rootName] = array();
				$root = &$_SESSION[$rootName];
			}

			// Store info from DB to session variables.
			foreach ($data[0] as $key => $value)
			{
				$root[$key] = $value;
			}

			session_regenerate_id(TRUE);

			$this->loader->getService("logger")->notice("User logged in. user={user}", ["method"=>__METHOD__, "user"=>implode(",",$data[0])]);
		}
		else
		{
			// Not found
			$this->loader->getService("logger")->warning("User not found or password not match. gets={user}", [
				"method"=>__METHOD__,
				"user"=>implode(",", $request->getQueryParams())
			]);
		}

		$request = $request->withAttribute("data", $data);
		$request = $request->withAttribute("resultCount", $db->resultCount);
		$request = $request->withAttribute("totalCount", $db->totalCount);

		return $request;

	}

}
