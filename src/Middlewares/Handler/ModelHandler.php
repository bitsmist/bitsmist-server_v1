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

namespace Bitsmist\v1\Middlewares\Handler;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Util\ModelUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Model based request handler class.
 */
class ModelHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$data = null;
		$method = strtolower($request->getMethod());

		$model = new ModelUtil();
		$methodName = $method . "Items";
		$data = $model->$methodName($request, $response);

		$request = $request->withAttribute("data", $data);
		$request = $request->withAttribute("resultCount", $model->resultCount);
		$request = $request->withAttribute("totalCount", $model->totalCount);

		return $request;

	}

}

