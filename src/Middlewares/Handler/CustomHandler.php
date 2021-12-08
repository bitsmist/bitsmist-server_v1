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

namespace Bitsmist\v1\Middlewares\Handler;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Closure;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Custom request handler class
// =============================================================================

class CustomHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		// Get a handler
		$method = strtolower($request->getMethod());
		$resource = strtolower($request->getAttribute("routeInfo")["args"]["resource"]);
		$rootDir = $request->getAttribute("appInfo")["rootDir"];
		$customHandler = $this->loadHandler($this->options["eventName"] ?? "", $method, $resource, $rootDir);

		if ($customHandler)
		{
			$func = Closure::bind($customHandler, $this);
			return $func($request, $handler);
		}
		else
		{
			return $handler->handle($request);
		}

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Load the request handler according to method, resource and event.
	 *
	 * @param	$eventName		An event name.
	 *
	 * @return	Handler.
     */
	private function loadHandler(?string $eventName = "", $method, $resource, $rootDir): ?callable
	{

		$ret = null;

		$fileName = $rootDir . "handlers/" . $method . "_" . $resource . ($eventName ? "_" : "") . $eventName . ".php";
		if (file_exists($fileName))
		{
			$ret  = require $fileName;
		}

		return $ret;

	}

}
