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

use Bitsmist\v1\Exceptions\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Host header validator class
// =============================================================================

class HostHeaderValidator extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * @throws HttpException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$hostHeader = $request->getHeaders()["host"][0] ?? "";
		$hostSetting = $request->getAttribute("settings")["options"]["name"] ?? "";

		// Check if host header is valid
		if ($hostHeader != $hostSetting)
		{
			$msg = sprintf("Invalid host. header=%s, setting=%s", $hostHeader, $hostSetting);

			$request->getAttribute("services")["logger"]->alert("{msg}", ["method" => __METHOD__, "msg" => $msg]);

			$e = new HttpException(HttpException::ERRMSG_PARAMETER, HttpException::ERRNO_PARAMETER);
			$e->setDetailMessage($msg);
			throw $e;
		}

		return $handler->handle($request);

	}

}
