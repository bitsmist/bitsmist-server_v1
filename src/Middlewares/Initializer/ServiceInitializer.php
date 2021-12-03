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

		foreach ((array)$spec["services"]["uses"] as $serviceName)
		{
			$serviceOptions = $spec[$serviceName];
			$className = $serviceOptions["className"];

			$services[$serviceName] = \Closure::bind(function ($c) use ($className, $serviceOptions) {
				return new $className($this->container, $serviceOptions);
			}, $app, get_class($app));
			/*
			$services[$serviceName] = function ($c) use ($className, $serviceOptions) {
				return new $className($this->container, $serviceOptions);
			};
			 */
		}

		$request = $request->withAttribute("services", $services);

		return $handler->handle($request);

	}

}
