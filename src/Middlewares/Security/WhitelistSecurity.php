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

namespace Bitsmist\v1\Middlewares\Security;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * White list based security checker class.
 */
class WhitelistSecurity extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$spec = $request->getAttribute("appInfo")["spec"];
		$method = strtolower($request->getMethod());
		$resource = $request->getAttribute("appInfo")["args"]["resource"];
		$gets = $request->getQueryParams();
		$posts = $request->getParsedBody();
		$whitelist = $spec["parameters"];

		// Check gets
		$this->checkWhitelist($gets, $whitelist, $method, $resource);

		// Check posts
		if (isset($posts["items"]))
		{
			foreach ($posts["items"] as $item)
			{
				$this->checkWhitelist($item, $whitelist, $method, $resource);
			}
		}

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
  	 * Check parameters against white list.
	 *
	 * @param	$appInfo		Application information.
	 * @param	$method			Method.
	 * @param	$resource		Resource.
	 *
	 * @throws	HttpException
     */
	private function checkWhiteList($target, $whitelist, $method, $resource)
	{

		foreach ($target as $key => $value)
		{
			if (!isset($whitelist[$key]))
			{
				$this->logger->alert("Invaild parameter: parameter = {key}, value = {value}, method = {method}, resource = {resource}", ["method"=>__METHOD__, "key"=>$key, "value"=>$value, "method"=>$method, "resource"=>$resource]);
				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
			}

			/*
			if ($whitelist[$key] && is_array($whitelist[$key]) && array_key_exists("type", $whitelist[$key]))
			{
				$checkList = explode(",", $whitelist[$key]["type"]);
				for ($i = 0; $i < count($checkList); $i++)
				{
					if (!Validator::validate($value, $checkList[$i]))
					{
						$this->logger->alert("Validation error: parameter = {key}, value = {value}, method = {method}, resource = {resource}", ["method"=>__METHOD__, "key"=>$key, "value"=>$value, "method"=>$method, "resource"=>$resource]);
						throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
					}
				}
			}
			 */
		}

	}

}
