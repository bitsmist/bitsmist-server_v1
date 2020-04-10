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
 * Query formatter class.
 */
class QueryFormatter extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$spec = $request->getAttribute("appInfo")["spec"];
		$params = $spec["parameters"] ?? array();
		$gets = $request->getAttribute("queryParams");

		foreach ($params as $param => $spec)
		{
			$type = $spec["fieldType"] ?? null;
			$format = $spec["format"] ?? null;
			$value = $gets[$param] ?? null;
			if ($type && $format && $value !== null)
			{
				$gets[$param] = FormatterUtil::format($value, strtolower($type), $format);
			}
		}

		return $request->withQueryParams($gets);

	 }

}
