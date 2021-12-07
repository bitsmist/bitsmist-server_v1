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
//	Data formatter class
// =============================================================================

class DataFormatter extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$fields = $this->options["fields"] ?? array();
		$params = $request->getAttribute("settings")["options"]["parameters"] ?? array();
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

		$request = $request->withAttribute("data", $data);

		return $handler->handle($request);

	 }

}
