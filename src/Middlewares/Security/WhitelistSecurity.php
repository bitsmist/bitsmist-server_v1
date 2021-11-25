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

namespace Bitsmist\v1\Middlewares\Security;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	White list based security checker class
// =============================================================================

class WhitelistSecurity extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$parameters = $this->loader->getAppInfo("spec")["options"]["parameters"] ?? null;
		if ($parameters)
		{
			// Align array format to associative array
			$whitelist = array();
			foreach ($parameters as $key => $value)
			{
				$key = ( is_numeric($key) ? $value : $key );
				$whitelist[$key] = $key;
			}

			$method = strtolower($request->getMethod());
			$resource = $this->loader->getRouteInfo("args")["resource"];

			// Check gets
			$gets = $request->getQueryParams();
			$this->checkWhitelist($gets, $whitelist, $method, $resource);

			// Check posts
			$itemsParamName = $this->options["specialParameters"]["items"] ?? "items";
			$posts = ($request->getParsedBody())[$itemsParamName] ?? null;
			foreach ((array)$posts as $item)
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
	private function checkWhiteList(array $target, array $whitelist, string $method, string $resource)
	{

		foreach ($target as $key => $value)
		{
			if (!isset($whitelist[$key]))
			{
				$this->loader->getService("logger")->alert("Invaild parameter: parameter = {key}, value = {value}, method = {method}, resource = {resource}", ["method"=>__METHOD__, "key"=>$key, "value"=>$value, "method"=>$method, "resource"=>$resource]);

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
						$this->loader->getService("logger")->alert("Validation error: parameter = {key}, value = {value}, method = {method}, resource = {resource}", ["method"=>__METHOD__, "key"=>$key, "value"=>$value, "method"=>$method, "resource"=>$resource]);
						throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
					}
				}
			}
			 */
		}

	}

}
