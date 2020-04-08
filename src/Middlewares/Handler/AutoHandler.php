<?php
// =============================================================================
/**
 * Bitsmist - PHP WebAPI Server Framework
 *
 * @copyright		Masaki Yasutake
 * @link			https://bitsmist.com/
 * @license			https://github.com/bitsmist/bitsmist/blob/master/LICENSE
 */
// =============================================================================

namespace Bitsmist\v1\Middlewares\Handler;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Middlewares\Handler\ModelHandler;
use Bitsmist\v1\Middlewares\Handler\CustomHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Request handler dispatcher class.
 */
class AutoHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$appInfo = $request->getAttribute("appInfo");
		$method = strtolower($request->getMethod());
		$resource = strtolower($request->getAttribute("appInfo")["args"]["resource"]);
		$loader = $request->getAttribute("loader");

		if ($loader->isHandlerExists())
		{
			$className = $appInfo["settings"]["middlewares"][$this->options["handlers"]["custom"]]["class"];
			$options = null;
		}
		else
		{
			$className = $appInfo["settings"]["middlewares"][$this->options["handlers"]["default"]]["class"];
			$options = null;
		}

		$middleware = new $className($options);
		if (method_exists($middleware, "setLogger"))
		{
			$middleware->setLogger($this->logger);
		}

		return $middleware($request, $response);;

	}

}

