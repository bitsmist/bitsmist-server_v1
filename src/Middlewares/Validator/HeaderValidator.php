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

namespace Bitsmist\v1\Middlewares\Validator;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	HTTP header validatorclass
// =============================================================================

class HeaderValidator extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * @throws HttpException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$logger = $request->getAttribute("services")["logger"];
		$headers = $request->getHeaders();
		$settings = $request->getAttribute("settings");

		/*
		// check host
		//if ($headers["host"][0] != $_SERVER["HTTP_HOST"])
		if ($headers["host"][0] != $_SERVER["SERVER_NAME"])
		{
			$logger->alert("Invalid host: header={headerHost}, host={host}", [
				"method"=>__METHOD__,
				"headerHost"=>$headers["host"][0],
				"host"=>$_SERVER["SERVER_NAME"],
			]);
			throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
		}
		 */

		// check if origin is set
		if ($settings["options"]["needOrigin"] ?? false)
		{
			if (!isset($headers["origin"][0]))
			{
				$logger->alert("Invalid origin: no origin", ["method"=>__METHOD__]);
				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
			}
		}

		// check if origin is in allowed origins list
		if (isset($headers["origin"][0]) && !in_array($headers["origin"][0], $settings["options"]["allowedOrigins"]))
		{
			$logger->alert("Invalid origin: origin={origin}", [
				"method"=>__METHOD__,
				"origin"=>($headers["origin"][0] ?? "")
			]);
			throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
		}

		// Check if required header exists
		foreach ((array)($settings["options"]["requiredHeaders"] ?? null) as $headerName)
		{
			if (!isset($headers[strtolower($headerName)][0]))
			{
				$logger->alert("Required header doesn't exist: headerName={headerName}", [
					"method"=>__METHOD__,
					"headerName"=>$headerName,
				]);
				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
			}
		}

		// Check if header exists when needPreflight option is true
		if ($settings["options"]["needPreflight"] ?? false)
		{
			$headerName = (is_string($settings["options"]["needPreflight"]) ? $settings["options"]["needPreflight"] : "X-From");
			if (!isset($headers[strtolower($headerName)][0]))
			{
				$logger->alert("Required header for preflight doesn't exist: headerName={headerName}", [
					"method"=>__METHOD__,
					"headerName"=>$headerName,
				]);
				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
			}
		}

		return $handler->handle($request);
	}

}
