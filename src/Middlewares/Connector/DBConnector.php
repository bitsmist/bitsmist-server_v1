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

namespace Bitsmist\v1\Middlewares\Connector;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;

// =============================================================================
//	DB connector class
// =============================================================================

class DBConnector extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$request->getAttribute("services")["db"]->open();

		return $handler->handle($request);

	}

}
