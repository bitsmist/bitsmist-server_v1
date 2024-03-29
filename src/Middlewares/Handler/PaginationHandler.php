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
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Pagination handler class
// =============================================================================

class PaginationHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$limit = 0;
		$offset = 0;
		$pagination = null;
		$settings = $request->getAttribute("settings");
		$limitParameterName = $settings["options"]["query"]["specialParameters"]["limit"] ?? "_limit";
		$offsetParameterName = $settings["options"]["query"]["specialParameters"]["offset"] ?? "_offset";
		$gets = $request->getQueryParams();

		if (strtolower($request->getMethod()) == "get")
		{
			$limit = $gets[$limitParameterName] ?? null;
			if($limit)
			{
				$offset = $gets[$offsetParameterName] ?? 0;
				list($page, $pageMax) = $this->getPagination($request->getAttribute("totalCount") ?? 0, $limit, $offset);
				$pagination = array("limit" => $limit, "offset" => $offset, "pageCurrent" => $page, "pageLast" => $pageMax);
			}
		}

		$request = $request->withAttribute("pagination", $pagination);

		return $handler->handle($request);

	}

    // -------------------------------------------------------------------------

	/**
	 * Calculate current page and max page.
	 *
	 * @param	$totalCount		Total record count.
	 * @param	$limiit			Limit.
	 * @param	$offset			Offset.
	 *
	 * @return	Current page and max page.
	 */
	protected function getPagination(int $totalCount, ?int $limit, ?int $offset): array
	{

		$page = 1;
		$pageMax = 1;

		if ($limit)
		{
           	$pageMax = ceil($totalCount / $limit);
		}

		if ($offset)
		{
			$page = floor($offset / $limit) + 1;
		}

		return array($page, $pageMax);

	}

}
