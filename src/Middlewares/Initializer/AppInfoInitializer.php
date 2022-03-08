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
use Bitsmist\v1\Utils\Util;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	AppInfo initializer class
// =============================================================================

class AppInfoInitializer extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$container = $request->getAttribute("container");
		$args = $request->getAttribute("routeInfo")["args"] ?? null;

		// App Info
		$appInfo = array();
		$appInfo["name"] = $args["appName"] ?? $_SERVER["HTTP_HOST"];
		$appInfo["version"] = $args["appVer"] ?? 1;
		$appInfo["lang"] = $args["appLang"] ?? "en";

		// Set setting vars dictionary
		$container["vars"]["appVer"] = $appInfo["version"];
		$container["vars"]["appName"] = $appInfo["name"];

		// App Info (appRoot)
		$appInfo["rootDir"] = $container["vars"]->replace($container["settings"]["options"]["appRoot"] ?? "{sysRoot}/sites/v{appVer}/{appName}");

		// Set setting vars dictionary (appRoot)
		$container["vars"]["appRoot"] = $appInfo["rootDir"];

		// Check app root
		if (!file_exists($appInfo["rootDir"]))
		{
			throw new \RuntimeException(sprintf("App root dir does not exist. rootDir=%s", $appInfo["rootDir"]));
		}

		$request = $request->withAttribute("appInfo", $appInfo);

		return $handler->handle($request);

	}

}
