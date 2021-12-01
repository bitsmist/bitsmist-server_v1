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
//	Extra header builder class.
// =============================================================================

class ExtraHeaderBuilder extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$response = $handler->handle($request);

		$extras = $request->getAttribute("spec")["options"]["extraHeaders"] ?? null;
		foreach ((array)$extras as $key => $value)
		{
			$response = $response->withHeader($key, $value);
		}

		$extras = $this->options["extraHeaders"] ?? null;
		foreach ((array)$extras as $key => $value)
		{
			$response = $response->withHeader($key, $value);
		}

		return $response;

	}

}
