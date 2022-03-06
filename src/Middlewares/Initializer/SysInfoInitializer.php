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

namespace Bitsmist\v1\Middlewares\Initializer;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Utils\Util;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	SysInfo initializer class
// =============================================================================

class SysInfoInitializer extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$settings = $request->getAttribute("container")["settings"];

		// System info
		$sysInfo = array();
		$sysInfo["version"] = $request->getAttribute("app")->getVersion();
		$sysInfo["rootDir"] = rtrim($settings["options"]["sysRoot"], "/");

		// Set setting vars dictionary
		Util::$replaceDic = array(
			"sysRoot" => $sysInfo["rootDir"],
			"sysVer" => $sysInfo["version"],
			"method" => strtolower($request->getMethod()),
		);

		$request = $request->withAttribute("sysInfo", $sysInfo);

		return $handler->handle($request);

	}

}
