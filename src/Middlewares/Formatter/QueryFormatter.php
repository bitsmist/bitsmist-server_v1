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
use Bitsmist\v1\Util\FormatterUtil;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Query formatter class
// =============================================================================

class QueryFormatter extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$params = $request->getAttribute("settings")["options"]["parameters"] ?? array();
		$gets = $request->getQueryParams();

		foreach ($params as $param => $spec)
		{
			$type = $spec["options"]["fieldType"] ?? null;
			$format = $spec["options"]["format"] ?? null;
			$value = $gets[$param] ?? null;
			if ($type && $format && $value !== null)
			{
				$gets[$param] = FormatterUtil::format($value, strtolower($type), $format);
			}
		}

		$request = $request->withQueryParams($gets);

		return $handler->handle($request);
	 }

}
