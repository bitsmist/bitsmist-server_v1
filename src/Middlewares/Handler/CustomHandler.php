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
use Bitsmist\v1\Utils\Util;
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

		$files = Util::replaceVars($this->getOption("uses"));
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
