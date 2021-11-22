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
use Bitsmist\v1\Util\ModelUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Login authenticator class
// =============================================================================

class LoginAuthenticator extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$data = null;
		$resultCount = 0;
		$totalCount = 0;

		// Get user data
		$model = new ModelUtil($this->loader);
		$data = $model->getItems($request, $response);
		$resultCount = $model->resultCount;
		$totalCount = $model->totalCount;

		if ($resultCount == 1)
		{
			// Found
			$spec = $this->loader->getAppInfo("spec");
			$rootName = $spec["options"]["sessionOptions"]["name"] ?? "";
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

			$this->loader->getService("loggerManager")->notice("User logged in. user={user}", ["method"=>__METHOD__, "user"=>implode(",",$data[0])]);
		}
		else
		{
			// Not found
			$this->loader->getService("loggerManager")->warning("User not found or password not match. gets={user}", [
				"method"=>__METHOD__,
				"user"=>implode(",", $request->getQueryParams())
			]);
		}

		$request = $request->withAttribute("data", $data);
		$request = $request->withAttribute("resultCount", $resultCount);
		$request = $request->withAttribute("totalCount", $totalCount);

		return $request;

	}

}
