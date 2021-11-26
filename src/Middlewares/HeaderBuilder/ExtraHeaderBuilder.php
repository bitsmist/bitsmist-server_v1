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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Extra header builder class.
// =============================================================================

class ExtraHeaderBuilder extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$extras = $this->loader->getAppInfo("spec")["options"]["extraHeaders"] ?? null;
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
