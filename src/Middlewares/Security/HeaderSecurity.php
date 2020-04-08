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
 * HTTP header security checker class.
 */
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
		$origins = $request->getAttribute("appInfo")["settings"]["options"]["allowedOrigins"];
		$spec = $request->getAttribute("appInfo")["spec"];

		// check host
		//if ($headers["HTTP_HOST"][0] != $_SERVER["SERVER_NAME"])
		if ($headers["host"][0] != $_SERVER["SERVER_NAME"])
		{
			//$this->logger->alert("Invalid host: host = {host}", ["method"=>__METHOD__, "host"=>$headers["HTTP_HOST"][0]]);
			$this->logger->alert("Invalid host: host = {host}", ["method"=>__METHOD__, "host"=>$headers["host"][0]]);
			throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
		}

		// check origin
		//if (isset($headers["HTTP_ORIGIN"][0]) && !in_array($headers["HTTP_ORIGIN"][0], $origins))
		if (isset($headers["origin"][0]) && !in_array($headers["origin"][0], $origins))
		{
			//$this->logger->alert("Invalid origin: origin = {origin}", ["method"=>__METHOD__, "origin"=>$headers["HTTP_ORIGIN"][0]]);
			$this->logger->alert("Invalid origin: origin = {origin}", ["method"=>__METHOD__, "origin"=>$headers["origin"][0]]);
			throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
		}

		// Check if x-from header exists when needPreflight option is true
		if ($spec["options"]["needPreflight"] ?? true)
		{
			//if (!isset($headers["HTTP_X_FROM"][0]))
			if (!isset($headers["x-from"][0]))
			{
				$this->logger->alert("Invalid x-from: x-from = null", ["method"=>__METHOD__]);
				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
			}
		}

	}

}
