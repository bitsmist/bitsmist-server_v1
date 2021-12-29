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
			$request->getAttribute("services")["logger"]->alert("Invalid host. header={header}, setting={setting}", [
				"method" => __METHOD__,
				"header" => $hostHeader,
				"setting" => $hostSetting,
			]);
			throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
		}

		return $handler->handle($request);

	}

}
