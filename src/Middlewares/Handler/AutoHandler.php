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
use Bitsmist\v1\Middlewares\Handler\DBHandler;
use Bitsmist\v1\Middlewares\Handler\CustomHandler;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Request handler dispatcher class
// =============================================================================

class AutoHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$settings = $request->getAttribute("settings");
		$rootDir = $request->getAttribute("appInfo")["rootDir"];
		$method = strtolower($_SERVER["REQUEST_METHOD"]);
		$resource = strtolower($request->getAttribute("routeInfo")["args"]["resource"]);

		if ($this->isHandlerExists($rootDir, $method, $resource))
		{
			$middlewareName = $this->options["handlers"]["custom"];
			$className = $settings[$middlewareName]["className"];
			$options = $settings[$middlewareName];
		}
		else
		{
			$middlewareName = $this->options["handlers"]["default"];
			$className = $settings[$middlewareName]["className"];
			$options = $settings[$middlewareName];
		}
		$middleware = new $className($options);

		return $middleware->process($request, $handler);

	}

	// -----------------------------------------------------------------------------

	/**
  	 * Check whether custom handler exists.
	 *
	 * @param	$appInfo		Application information.
	 * @param	$method			Method.
	 * @param	$resource		Resource.
	 *
	 * @return	Exists or not.
     */
	public function isHandlerExists(string $rootDir, string $method, string $resource, ?string $eventName = ""): bool
	{

		$ret = false;

		$fileName = $rootDir . "handlers/" . $method . "_" . $resource . ($eventName ? "_" : "") . $eventName . ".php";
		if (is_readable($fileName))
		{
			$ret = true;
		}

		return $ret;

	}

}
