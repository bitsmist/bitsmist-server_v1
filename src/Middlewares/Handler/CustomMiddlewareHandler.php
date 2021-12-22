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
//	Custom middleware handler class
// =============================================================================

class CustomMiddlewareHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Middlewares.
	 *
	 * @var		array
	 */
	protected $handlers = array();

	/**
	 * Next handler.
	 *
	 * @var		callable
	 */
	protected $nextHandler = null;

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$this->nextHandler = $handler;

		// Get custom handlers
		$files = $this->replaceVars($request, $this->getOption("uses"));
		$this->handlers = $this->getHandlers($files);

		reset($this->handlers);
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

		// Get next custom middleware
		$middleware = current($this->handlers);
		next($this->handlers);

		if (is_callable($middleware))
		{
			// Execute
			$ret = $middleware($request, $this);
		}
		else
		{
			// Call middleware chain
			$ret = $this->nextHandler->handle($request);
		}

		return $ret;

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
  	 * Replace variables in file names.
	 *
	 * @param	$request		Request.
	 * @param	$files			Setting file names.
	 *
	 * @return	Replaced file names.
     */
	protected function replaceVars($request, $files)
	{

		$sysInfo = $request->getAttribute("sysInfo");
		$appInfo = $request->getAttribute("appInfo");
		$args = $request->getAttribute("routeInfo")["args"];
		$sysRoot = $sysInfo["rootDir"];
		$appRoot = $appInfo["rootDir"];

		$argKeys = array_map(function($x){return "{" . $x . "}";}, array_keys($args));
		$from = array_merge(["{sysRoot}", "{appRoot}", "{sysVer}", "{appVer}", "{method}"], $argKeys);
		$to = array_merge([$sysRoot, $appRoot, $sysInfo["version"], $appInfo["version"], $request->getMethod()], array_values($args));

		return str_replace($from, $to, $files);

	}

	// -------------------------------------------------------------------------

	/**
  	 * Load handler files.
	 *
	 * @param	$files			Handler file names.
	 *
	 * @return	array
     */
	protected function getHandlers($files): array
	{

		$handlers = array();

		foreach ((array)$files as $fileName)
		{
			if (file_exists($fileName))
			{
				$handlers[] = require $fileName;
			}
		}

		return $handlers;

	}

}
