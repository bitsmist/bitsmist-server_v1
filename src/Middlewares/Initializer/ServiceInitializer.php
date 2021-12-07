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

namespace Bitsmist\v1\Middlewares\Initializer;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Util\Util;
use Pimple\Container;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Service initializer class
// =============================================================================

class ServiceInitializer extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$container = $request->getAttribute("container");
		$spec = $request->getAttribute("spec");

		foreach ((array)$spec["services"]["uses"] as $key => $value)
		{
			if (is_numeric($key))
			{
				// Does not have options
				$title = $value;
				$options = null;
			}
			else
			{
				// Has options
				$title = $key;
				$options = $value;
			}

			$container["services"][$title] = function ($c) use ($title, $options, $container) {
				// Merge settings
				$options = array_merge($container["settings"][$title] ?? array(), $options ?? array());

				// Create an instance
				return Util::resolveInstance($options, $container, $options);
			};
		}

		return $handler->handle($request);

	}

}
