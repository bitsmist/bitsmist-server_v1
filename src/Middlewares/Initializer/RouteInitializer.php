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

use Bitsmist\v1\Exceptions\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Utils\Util;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Route initializer class
// =============================================================================

class RouteInitializer extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$container = $request->getAttribute("container");
		$className = $container["settings"]["router"]["className"] ?? "nikic\FastRoute";
		$routes = $container["settings"]["router"]["routes"];
		$routeInfo = null;

		switch ($className)
		{
		case "nikic\FastRoute":
			$routeInfo = $this->loadRoute_FastRoute($routes);
			break;
		}

		// Set setting vars dictionary
		$container["vars"]->merge($routeInfo["args"]);

		$request = $request->withAttribute("routeInfo", $routeInfo);

		return $handler->handle($request);

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
  	 * Load route using nikic/FastRoute.
	 *
	 * @param	$routes			Routes.
	 *
	 * @return	Route arguments.
     */
	private function loadRoute_FastRoute($routeSettings)
	{

		$dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use ($routeSettings) {
			foreach ($routeSettings as $routeName => $route)
			{
				$routes = array();

				// One route
				if ($route["route"] ?? "")
				{
					$routes[] = $route;
				}
				// Multiple routes
				else if ($route["routes"] ?? "")
				{
					$routes = array_merge($routes, $route["routes"]);
				}

				// Add routes
				for ($i = 0; $i < count($routes) ; $i++)
				{
					$methods = explode(",", $routes[$i]["method"] ?? "GET,POST,PUT,PATCH,DELETE,OPTIONS");
					$handler = $routes[$i]["handler"] ?? "default";
					$r->addRoute($methods, $routes[$i]["route"], $handler);
				}
			}
		});

		$routeinfo = $dispatcher->dispatch($_SERVER["REQUEST_METHOD"], $_SERVER["REQUEST_URI"]);

		$args = null;
		switch ($routeinfo[0])
		{
		case \FastRoute\Dispatcher::NOT_FOUND:
			header("HTTP/1.1 404 OK\r\n");
			throw new HttpException(HttpException::ERRMSG_PARAMETER_INVALIDROUTE, HttpException::ERRNO_PARAMETER_INVALIDROUTE);
			break;
		case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
			header("HTTP/1.1 405 OK\r\n");
			throw new HttpException(HttpException::ERRMSG_PARAMETER_INVALIDMETHOD, HttpException::ERRNO_PARAMETER_INVALIDMETHOD);
			break;
		case \FastRoute\Dispatcher::FOUND:
			$routeName = $routeinfo[1];
			if (($routes[$routeName]["handler"] ?? "default") == "reject")
			{
				header("HTTP/1.1 " . ($routes[$routeName]["status"] ?? "404") . " OK\r\n");
				exit;
			}
			$args = $routeinfo[2];
			break;
		}

		return array(
			"name" => $routeName,
			"args" => $args,
		);

	}

}
