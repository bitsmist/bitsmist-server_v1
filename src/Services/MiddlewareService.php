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

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Utils\Util;
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
	protected ?ServerRequestInterface $request = null;

	/**
	 * Plugin names to iterate.
	 *
	 * @var		array
	 */
	protected $pluginNames = null;

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Get request object.
	 *
	 * @return	Request object.
	 */
	public function getRequest(): ?ServerRequestInterface
	{

		return $this->request;

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
			$this->plugins[$middleware] = function ($c) use ($middleware, $options) {
				try
				{
					// Merge settings
					$options = array_merge($this->container["settings"][$middleware] ?? array(), $options ?? array());

					// Get instance
					return Util::resolveInstance($options, $middleware, $options, $this->container);
				}
				catch (\Throwable $e)
				{
					throw new \RuntimeException("Failed to create a middleware. middlewearName=" . $middleware . ", reason=" . $e->getMessage());
				}
			};
		}
		else
		{
			$hash = spl_object_hash($middleware);
			$this->plugins[$hash] = $middleware;
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

		$this->pluginNames = $this->plugins->keys();
		reset($this->pluginNames);

		return $this->handle($request);

	}

	// -------------------------------------------------------------------------

	/**
	 * Handle a request.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Response.
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{

		$this->request = $request;

		// Get next enabled middleware
		do {
			$middlewareName = current($this->pluginNames);
			$middleware = ( $middlewareName ? $this->plugins[$middlewareName] : null );
			next($this->pluginNames);
		} while ($middleware instanceof MiddlewareBase && $middleware->getOption("enabled") === false);

		// Execute
		if ($middleware instanceof MiddlewareInterface)
		{
			$ret = $middleware->process($request, $this);
		}
		else if (is_callable($middleware))
		{
			$ret = $middleware($request, $this);
		}
		else
		{
			// Get a respose
			$ret = $this->container["response"];
		}

		return $ret;

	}

}
