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

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$spec = $this->loader->getAppInfo("spec");
		$params = $spec["options"]["parameters"] ?? array();
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

		return $request->withQueryParams($gets);

	 }

}
