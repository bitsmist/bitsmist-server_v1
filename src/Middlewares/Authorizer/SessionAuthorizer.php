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

namespace Bitsmist\v1\Middlewares\Authorizer;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Session authorizer class.
 */
class SessionAuthorizer extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$isAuthorized = false;
		if ( isset($_SESSION["USER"]) )
		{
			$isAuthorized = true;
		}

		if (!$isAuthorized)
		{
			$method = $request->getMethod();
			$resource = $request->getAttribute("appInfo")["args"]["resource"];

			$this->logger->alert("Not authorized: method = {httpmethod}, resource = {resource}", ["method"=>__METHOD__, "httpmethod"=>$method, "resource"=>$resource]);
			throw new HttpException(HttpException::ERRNO_PARAMETER_NOTAUTHORIZED, HttpException::ERRMSG_PARAMETER_NOTAUTHORIZED);
		}

		return;

	}

}
