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
//	Required header validator class
// =============================================================================

class RequiredHeaderValidator extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * @throws HttpException
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$headers = $request->getHeaders();

		// Check if required header exists
		foreach ((array)($request->getAttribute("settings")["options"]["requiredHeaders"] ?? null) as $headerName)
		{
			if (!isset($headers[strtolower($headerName)][0]))
			{
				$request->getAttribute("services")["logger"]->alert("Required header doesn't exist. headerName={headerName}", [
					"method" => __METHOD__,
					"headerName" => $headerName,
				]);
				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
			}
		}

		return $handler->handle($request);

	}

}
