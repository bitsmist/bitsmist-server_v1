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

namespace Bitsmist\v1\Middlewares\Formatter;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Utils\FormatterUtil;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Query limiter class
// =============================================================================

class QueryLimiter extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$options = $this->options ?? array();
		$gets = $request->getQueryParams();

		// Limit
		if (array_key_exists("maxLimit", $options))
		{
			$limit = $gets["_limit"] ?? 0;
			if ($limit > $options["maxLimit"])
			{
				$gets["_limit"] = $options["maxLimit"];
			}
		}

		// Offset
		if (array_key_exists("maxOffset", $options))
		{
			$limit = $gets["_offset"] ?? 0;
			if ($limit > $options["maxOffset"])
			{
				$gets["_offset"] = $options["maxOffset"];
			}
		}

		$request = $request->withQueryParams($gets);

		return $handler->handle($request);
	 }

}
