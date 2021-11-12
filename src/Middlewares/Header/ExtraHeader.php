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
 * Extra header class.
 */
class ExtraHeader extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$extras = $request->getAttribute("appInfo")["spec"]["options"]["extraHeaders"] ?? null;
		if ($extras)
		{
			foreach ($extras as $key => $value)
			{
				$response = $response->withHeader($key, $value);
			}
		}

		$extras = $this->options["extraHeaders"] ?? null;
		if ($extras)
		{
			foreach ($extras as $key => $value)
			{
				$response = $response->withHeader($key, $value);
			}
		}

		return $response;

	}

}
