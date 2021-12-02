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

use Bitsmist\v1\Exception\HttpException;
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
			// Create an instance
			$options = array_merge($this->container["spec"][$middleware] ?? array(), $options ?? array());
			$className = $options["className"] ?? null;
			$this->plugins[] = new $className($options);
		}
		else
		{
			$this->plugins[] = $middleware;
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

		reset($this->plugins);

		$request = $request->withAttribute("resultCode", HttpException::ERRNO_NONE);
		$request = $request->withAttribute("resultMessage", HttpException::ERRMSG_NONE);
		$request = $request->withAttribute("spec", $this->container["spec"]);
		$request = $request->withAttribute("routeInfo", $this->container["routeInfo"]);
		$request = $request->withAttribute("appInfo", $this->container["appInfo"]);
		$request = $request->withAttribute("sysInfo", $this->container["sysInfo"]);
		$request = $request->withAttribute("services", $this->container["services"]);

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

		// Get a middleware
		$middleware = current($this->plugins);
		next($this->plugins);

		// Execute
		if (is_callable($middleware))
		{
			$ret = $middleware($request, $this);
		}
		else if ($middleware instanceof MiddlewareInterface)
		{
			$ret = $middleware->process($request, $this);
		}
		else
		{
			$ret = $this->container["response"];
		}

		return $ret;

	}

}
