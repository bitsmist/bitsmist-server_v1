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

namespace Bitsmist\v1\Middlewares\Security;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Spec file security checker class.
// =============================================================================

class SpecSecurity extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$method = strtolower($request->getMethod());
		$resource = strtolower($this->loader->getRouteInfo()["args"]["resource"]);
		$spec = $this->loader->getAppInfo("spec");

		if (!$spec["lastSpecFile"] || $spec["lastSpecFile"] != ($method . "_" . $resource))
		{
			$this->loader->getService("loggerManager")->alert("No handler: lastSpecFile = {lastSpecFile}, method = {httpmethod}, resource = {resource}", [
				"method" => __METHOD__,
				"lastSpecFile" => $spec["lastSpecFile"],
				"httpmethod" => $method,
				"resource" => $resource]
			);

			throw new HttpException(HttpException::ERRNO_PARAMETER_INVALIDRESOURCE, HttpException::ERRMSG_PARAMETER_INVALIDRESOURCE);
		}

	}

}
