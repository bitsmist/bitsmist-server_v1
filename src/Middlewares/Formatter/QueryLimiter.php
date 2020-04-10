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

namespace Bitsmist\v1\Middlewares\Formatter;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Util\FormatterUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Query limiter class.
 */
class QueryLimiter extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$spec = $request->getAttribute("appInfo")["spec"];
		$options = $spec["options"] ?? array();
		$gets = $request->getAttribute("queryParams");

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

		return $request->withAttribute("queryParams", $gets);

	 }

}
