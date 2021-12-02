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

use Bitsmist\v1\Services\MiddlewareService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Error controller class.
// =============================================================================

class ErrorControllerService extends MiddlewareService
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Handle an error.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Response.
	 */
	public function dispatch(ServerRequestInterface $request): ResponseInterface
	{

		// Rethrow an exeption when no error handler available
		if (count($this->plugins) == 0)
		{
			throw $request->getAttribute("exception");
		}

		reset($this->plugins);

		//$request = $request->withAttribute("resultCode", HttpException::ERRNO_NONE);
		//$request = $request->withAttribute("resultMessage", HttpException::ERRMSG_NONE);
		$request = $request->withAttribute("spec", $this->container["spec"]);
		$request = $request->withAttribute("routeInfo", $this->container["routeInfo"]);
		$request = $request->withAttribute("appInfo", $this->container["appInfo"]);
		$request = $request->withAttribute("sysInfo", $this->container["sysInfo"]);
		$request = $request->withAttribute("services", $this->container["services"]);

		return $this->handle($request);

	}

}
