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
use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Custom request handler class.
 */
class CustomHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$eventName = ($this->options && array_key_exists("event", $this->options) ? $this->options["event"] : "");
		$loader = $request->getAttribute("loader");
		$handler = $loader->loadHandler($eventName);

		$ret = null;
		if ($handler)
		{
			$func = Closure::bind($handler, $this, get_class($this));
			$ret = $func($request, $response);
		}

		return $ret;

	}

}

