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

namespace Bitsmist\v1\Managers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Middlware manager class
// =============================================================================

class MiddlewareManager extends PluginManager
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
	 * Process middleware chains.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Response.
	 */
	public function process(ServerRequestInterface $request, ResponseInterface $response): array
	{

		foreach ($this->plugins as $middlewareName => $middleware)
		{
			$ret = null;

			if (is_callable($middleware))
			{
				$ret = $middleware($request, $response);
			}

			if ($ret instanceof \Psr\Http\Message\RequestInterface)
			{
				$request = $ret;
			}

			if ($ret instanceof \Psr\Http\Message\ResponseInterface)
			{
				$response = $ret;
			}
		}

		return array($request, $response);

	}

	// -------------------------------------------------------------------------

	/**
	 * Process middleware chains.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Response.
	 */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response): array
	{

		return $this->process($request, $response);

	}

}
