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
use Bitsmist\v1\Middlewares\Handler\ModelHandler;
use Bitsmist\v1\Middlewares\Handler\CustomHandler;
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

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$spec = $request->getAttribute("appInfo")["spec"];

		if ($this->loader->isHandlerExists())
		{
			$className = $spec[$this->options["handlers"]["custom"]]["className"];
			$options = array();
		}
		else
		{
			$className = $spec[$this->options["handlers"]["default"]]["className"];
			$options = array();
		}
		$middleware = new $className($this->loader, $options);

		return $middleware($request, $response);

	}

}
