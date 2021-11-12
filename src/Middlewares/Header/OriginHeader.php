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

namespace Bitsmist\v1\Middlewares\Header;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Origin header class.
 */
class OriginHeader extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$origins = $request->getAttribute("appInfo")["spec"]["options"]["allowedOrigins"] ?? null;
		$headers = $request->getHeaders();

		//if (array_key_exists("HTTP_ORIGIN", $headers))
		if ($origins && array_key_exists("origin", $headers))
		{
			//$allowedOrigin = $this->getAllowedOrigin($headers["HTTP_ORIGIN"][0], $origins);
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
