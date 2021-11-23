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
//	HTTP header security checker class
// =============================================================================

class HeaderSecurity extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * @throws HttpException
	 */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$headers = $request->getHeaders();
		$spec = $this->loader->getAppInfo("spec");

		// check host
		if ($headers["host"][0] != $_SERVER["SERVER_NAME"])
		{
			$this->loader->getService("logger")->logger->alert("Invalid host: host = {host}", [
				"method"=>__METHOD__,
				"host"=>$headers["host"][0]
			]);
			throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
		}

		// check if origin is set
		if ($spec["options"]["needOrigin"] ?? false)
		{
			if (!isset($headers["origin"][0]))
			{
				$this->loader->getService("logger")->alert("Invalid origin: no origin", ["method"=>__METHOD__]);
				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
			}
		}

		// check if origin is in allowed origins list
		if (isset($headers["origin"][0]) && !in_array($headers["origin"][0], $spec["options"]["allowedOrigins"]))
		{
			$this->loader->getService("logger")->alert("Invalid origin: origin = {origin}", [
				"method"=>__METHOD__,
				"origin"=>($headers["origin"][0] ?? "")
			]);
			throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
		}

		// Check if required header exists
		if ($spec["options"]["requiredHeaders"] ?? false)
		{
			foreach ($spec["options"]["requiredHeaders"] as $headerName)
			{
				if (!isset($headers[strtolower($headerName)][0]))
				{
					$this->loader->getService("logger")->alert("Required header doesn't exist: headerName = {headerName}", [
						"method"=>__METHOD__,
						"headerName"=>$headerName,
					]);
					throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
				}
			}
		}

		// Check if header exists when needPreflight option is true
		if ($spec["options"]["needPreflight"] ?? false)
		{
			$headerName = (is_string($spec["options"]["needPreflight"]) ? $spec["options"]["needPreflight"] : "X-From");
			if (!isset($headers[strtolower($headerName)][0]))
			{
				$this->loader->getService("logger")->alert("Required header for preflight doesn't exist: headerName = {headerName}", [
					"method"=>__METHOD__,
					"headerName"=>$headerName,
				]);
				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
			}
		}
	}

}
