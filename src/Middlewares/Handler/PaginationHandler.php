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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Pagination handler class.
 */
class PaginationHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$method = strtolower($request->getMethod());
		$gets = $request->getQueryParams();
		$totalCount = $request->getAttribute("totalCount");
		$limit = 0;
		$offset = 0;
		$pagination = null;

		if ($method == "get")
		{
			$limit = $gets["_limit"] ?? null;
			if($limit)
			{
				$offset = $gets["_offset"] ?? 0;
				list($page, $pageMax) = $this->getPagination($totalCount, $limit, $offset);
				$pagination = array("pageCurrent" => $page, "pageLast" => $pageMax);
			}
		}

		$request = $request->withAttribute("pagination", $pagination);

		return $request;

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

