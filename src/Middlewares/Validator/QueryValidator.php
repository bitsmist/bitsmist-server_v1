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

namespace Bitsmist\v1\Middlewares\Validator;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Query validator class.
 */
class QueryValidator extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$spec = $request->getAttribute("appInfo")["spec"];
		$params = $spec["parameters"] ?? array();
		$gets = $request->getQueryParams();

		foreach ($params as $param => $spec)
		{
			$validations = $spec["validator"] ?? [];
			for ($i = 0; $i < count($validations); $i++)
			{
				switch (strtolower($validations[$i]))
				{
				case "required":
					if (!($gets[$param] ?? null))
					{
						throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
					}
					break;
				}
			}
		}

		return $request->withQueryParams($gets);

	 }

}
