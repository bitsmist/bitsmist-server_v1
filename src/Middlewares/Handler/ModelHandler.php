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
use Bitsmist\v1\Util\ModelUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Model based request handler class
// =============================================================================

class ModelHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$options = $this->loader->getAppInfo("spec")["options"] ?? array();
		$method = strtolower($request->getMethod());
		$data = null;

		$model = new ModelUtil($this->loader);
		$methodName = $method . "Items";
		$data = $model->$methodName($request, $response);

		/*
		// Retry with another offset when offset is too big.
		if (array_key_exists("retryOffset", $options))
		{
			if ($method == "get")
			{
				$gets = $request->getQueryParams();
				$offset = $gets["_offset"] ?? 0;
				if ($offset > $model->totalCount)
				{
					$gets["_offset"] = $options["retryOffset"];
					$request = $request->withQueryParams("queryParams", $gets);
					$data = $model->$methodName($request, $response);
				}
			}
		}
		 */

		$request = $request->withAttribute("data", $data);
		$request = $request->withAttribute("resultCount", $model->resultCount);
		$request = $request->withAttribute("totalCount", $model->totalCount);

		return $request;

	}

}

