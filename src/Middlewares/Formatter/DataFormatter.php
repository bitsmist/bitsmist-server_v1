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
//	Data formatter class
// =============================================================================

class DataFormatter extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$spec = $request->getAttribute("appInfo")["spec"];
		$fields = $spec["options"]["fields"] ?? array();
		$params = $spec["options"]["parameters"] ?? array();
		$gets = $request->getQueryParams();
		$data = $request->getAttribute("data");

		if ($data)
		{
			for ($i = 0; $i < count($data); $i++)
			{
				foreach($data[$i] as $fieldName => $value)
				{
					$type = $fields[$fieldName]["type"] ?? null;
					$format = $fields[$fieldName]["format"] ?? null;
					if ($value && $type && $format)
					{
						$data[$i][$fieldName] = FormatterUtil::format($value, strtolower($type), $format);
					}
				}
			}
		}

		return $request->withAttribute("data", $data);

	 }

}
