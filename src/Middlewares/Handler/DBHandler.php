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
use Bitsmist\v1\Utils\DBUtil;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Database handler class
// =============================================================================

class DBHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$dbCount = count($request->getAttribute("services")["db"]);
		if ($dbCount > 0)
		{
			// Handle database
			$db = new DBUtil($this->options);
			$methodName = strtolower($request->getMethod()) . "Items";
			$data = $db->$methodName($request);

			$request = $request->withAttribute("data", $data);
			$request = $request->withAttribute("resultCount", $db->resultCount);
			$request = $request->withAttribute("totalCount", $db->totalCount);
		}

		return $handler->handle($request);

	}

}
