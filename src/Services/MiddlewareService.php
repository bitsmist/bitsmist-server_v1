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

namespace Bitsmist\v1\Services;

use Bitsmist\v1\Util\Util;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Middlware service class
// =============================================================================

class MiddlewareService extends PluginService implements  RequestHandlerInterface
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Request.
	 *
	 * @var		Request
	 */
	public $request = null;

	/**
	 * Plugin names.
	 *
	 * @var		array
	 */
	protected $pluginNames = array();

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Get middlewares.
	 *
	 * @return	Middlewares.
	 */
	public function getMiddlewares(): array
	{

		return $this->plugins;

	}

	// -------------------------------------------------------------------------

	/**
	 * Add a middleware.
	 *
	 * @param	$middleware		Middleware instance or middleware name.
	 * @param	$options		Middleware options.
	 *
	 * @return	Added middleware.
	 */
	public function add($middleware, ?array $options)
	{

		if (is_string($middleware))
		{
			$this->pluginNames[] = $middleware;
			$this->plugins[$middleware] = function ($c) use ($middleware, $options) {
				$options = array_merge($this->container["settings"][$middleware] ?? array(), $options ?? array());
				return Util::resolveInstance($options, $options);
			};
		}
		else
		{
			$title = spl_object_hash($middleware);
			$this->pluginNames[] = $title;
			$this->plugins[$title] = function ($c) use ($middleware) {
				return $middleware;
			};
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Dispatch middleware chains.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Response.
	 */
	public function dispatch(ServerRequestInterface $request): ResponseInterface
	{

		reset($this->pluginNames);

		return $this->handle($request);

	}

	// -------------------------------------------------------------------------

	/**
	 * Handle an request.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Response.
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{

		$this->request = $request;

		$title = current($this->pluginNames);
		next($this->pluginNames);

		if ($title)
		{
			// Get a middleware
			$middleware = $this->plugins[$title];

			// Execute
			if (is_callable($middleware))
			{
				$ret = $middleware($request, $this);
			}
			else if ($middleware instanceof MiddlewareInterface)
			{
				$ret = $middleware->process($request, $this);
			}
		}
		else
		{
			// Get a respose
			$ret = $this->container["response"];
		}

		return $ret;

	}

}
