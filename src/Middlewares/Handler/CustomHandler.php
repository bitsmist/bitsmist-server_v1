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
//	Custom handler class
// =============================================================================

class CustomHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$files = $this->replaceVars($request, $this->getOption("uses"));
		list ($reqHandlers, $resHandlers) = $this->getHandlers($files);

		// Handle request
		$request = $this->execHandlers($reqHandlers, $request);

		// Call middleware chain
		$response = $handler->handle($request);

		// Handle response
		$response = $this->execHandlers($resHandlers, $response);

		return $response;

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
  	 * Get request/response handlers.
	 *
	 * @param	$files			Handler file names.
	 *
	 * @return	array.
     */
	protected function getHandlers($files)
	{

		$reqHandlers = array();
		$resHandlers = array();

		foreach ((array)$files as $fileName)
		{
			if (file_exists($fileName))
			{
				$handlers = require $fileName;

				if ($handlers[0])
				{
					$reqHandlers[] = $handlers[0];
				}

				if ($handlers[1])
				{
					$resHandlers[] = $handlers[1];
				}
			}
		}

		return array($reqHandlers, $resHandlers);

	}

	// -------------------------------------------------------------------------

	/**
  	 * Load handler files and execute them.
	 *
	 * @param	$request		Request.
     */
	protected function execHandlers($handlers, $target = null)
	{

		foreach ((array)$handlers as $handler)
		{
			$ret = $handler($target);

			$target = ($ret ?? $target);
		}

		return $target;

	}

}
