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

		$app = $request->getAttribute("app");
		$spec = $request->getAttribute("spec");
		$services = new Container();

		foreach ((array)$spec["services"]["uses"] as $key => $value)
		{
			if (is_numeric($key))
			{
				// Does not have options
				$title = $value;
				$serviceOptions = null;
			}
			else
			{
				// Has options
				$title = $key;
				$serviceOptions = $value;
			}

			// Merge settings
			$options = array_merge($spec[$title] ?? array(), $serviceOptions ?? array());

			$services[$title] = \Closure::bind(function ($c) use ($options) {
				// Create an instance
				return Util::resolveInstance($options, $this->container, $options);
			}, $app, get_class($app));
		}

		$request = $request->withAttribute("services", $services);

		return $handler->handle($request);

	}

}
