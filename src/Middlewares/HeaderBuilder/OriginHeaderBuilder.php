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

namespace Bitsmist\v1\Middlewares\HeaderBuilder;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Origin header builder class
// =============================================================================

class OriginHeaderBuilder extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$response = $handler->handle($request);

		$origins = $request->getAttribute("settings")["options"]["allowedOrigins"] ?? null;
		$headers = $request->getHeaders();

		if ($origins && array_key_exists("origin", $headers))
		{
			$allowedOrigin = $this->getAllowedOrigin($headers["origin"][0], $origins);
			if ($allowedOrigin)
			{
				$response = $response->withHeader("Access-Control-Allow-Origin", $allowedOrigin);
			}
		}

		return $response;

	}

	// -----------------------------------------------------------------------------

	/**
	 * Return allowed origin if the host exists in the setting.
	 *
	 * @param	$host			Host name passed via HTTP.
	 * @param	$origins		Allowed origins list in the setting.
	 *
	 * @return	Allowed origin.
	 */
	public function getAllowedOrigin(string $host, array $origins): string
	{

		$allowedOrigin = "";

		if (is_array($origins) && in_array($host, $origins))
		{
			$allowedOrigin = $host;
		}

		return $allowedOrigin;

	}

}
