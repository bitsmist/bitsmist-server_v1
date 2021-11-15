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

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Result builder class
// =============================================================================

class ResultHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$result = $this->buildResult(
			$request->getAttribute("resultCode"),
			$request->getAttribute("resultMessage"),
			$request->getAttribute("resultCount"),
			$request->getAttribute("totalCount"),
			$request->getAttribute("data"),
			$request->getAttribute("pagination"),
		);

		return $request->withAttribute("result", $result);

	}

	// -----------------------------------------------------------------------------

	/**
	 * Build result array.
	 *
	 * @param	$resultCode		Result code.
	 * @param	$resultMessage	Result message.
	 * @param	$resultCount	Record count.
	 * @param	$totalCount		Total record count.
	 * @param	$data			Result data.
	 * @param	$pagination		Pagination data.
	 *
	 * @return	Result data.
	 */
	public static function buildResult(int $resultCode = HttpException::ERRNO_NONE, ?string $resultMessage = "", ?int $resultCount = 0, ?int $totalCount = 0, ?array $data = null, ?array $pagination = null): ?array
	{

		if ($resultCount > $totalCount)
		{
			$totalCount = $resultCount;
		}

		$result = [
			"result" => [
				"resultCode"		=>	$resultCode,
				"resultMessage"		=>	$resultMessage,
				"resultCount"		=>	$resultCount,
				"totalCount"		=>	$totalCount,
			]
		];

		$result["data"] = $data;

		if ($pagination)
		{
			$result["pagination"] = $pagination;
		}

		return $result;

	}

}
