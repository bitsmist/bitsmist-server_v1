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

namespace Bitsmist\v1\Middlewares\Security;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Spec file security checker class.
 */
class SpecSecurity extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$method = strtolower($request->getMethod());
		$resource = strtolower($request->getAttribute("appInfo")["args"]["resource"]);
		$spec = $request->getAttribute("appInfo")["spec"];

		if (!$spec["lastSpecFile"] || $spec["lastSpecFile"] != ($method . "_" . $resource))
		{
			$this->logger->alert("No handler: lastSpecFile = {lastSpecFile}, method = {httpmethod}, resource = {resource}", ["method"=>__METHOD__, "lastSpecFile"=>$spec["lastSpecFile"], "httpmethod"=>$method, "resource"=>$resource]);
			throw new HttpException(HttpException::ERRNO_PARAMETER_INVALIDRESOURCE, HttpException::ERRMSG_PARAMETER_INVALIDRESOURCE);
		}

	}

}
