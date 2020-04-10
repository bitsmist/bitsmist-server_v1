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

namespace Bitsmist\v1\Middlewares\Authenticator;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Util\ModelUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Login authenticator class.
 */
class LoginAuthenticator extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$gets = $request->getAttribute("queryParams");
		$dbs = $request->getAttribute("databases");
		$spec = $request->getAttribute("appInfo")["spec"];

		$data = null;
		$resultCount = 0;
		$totalCount = 0;

		// Get user data
		$model = new ModelUtil();
		$methodName = strtolower($request->getMethod()) . "Items";
		$data = $model->$methodName($request, $response);
		$resultCount = $model->resultCount;
		$totalCount = $model->totalCount;
		if ($resultCount == 1)
		{
			// Found
			session_start();
			session_regenerate_id(TRUE);
			$user_id = $spec["options"]["userId"] ?? "";
			$user_name = $spec["options"]["userName"] ?? "";
			$_SESSION["USER"] = [
				"ID" => $data[0][$user_id],
				"NAME" => $data[0][$user_name],
				"DATA" => $data[0],
			];
			$this->logger->notice("User logged in. user={user}", ["method"=>__METHOD__, "user"=>implode(",",$data[0])]);
		}
		else
		{
			// Not found
			$this->logger->warning("User not found or password not match. gets={user}", ["method"=>__METHOD__, "user"=>implode(",", $gets)]);
			$data = null;
			$resultCount = 0;
			$totalCount = 0;
		}

		$request = $request->withAttribute("data", $data);
		$request = $request->withAttribute("resultCount", $resultCount);
		$request = $request->withAttribute("totalCount", $totalCount);

		return $request;

	}

}
