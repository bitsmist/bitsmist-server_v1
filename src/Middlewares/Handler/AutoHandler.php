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

		$spec = $this->loader->getAppInfo("spec");

		if ($this->loader->isHandlerExists())
		{
			$middlewareName = $this->options["handlers"]["custom"];
			$className = $spec[$middlewareName]["className"];
			$options = $spec[$middlewareName];
		}
		else
		{
			$middlewareName = $this->options["handlers"]["default"];
			$className = $spec[$middlewareName]["className"];
			$options = $spec[$middlewareName];
		}
		$middleware = new $className($this->loader, $options);

		return $middleware($request, $response);

	}

}
