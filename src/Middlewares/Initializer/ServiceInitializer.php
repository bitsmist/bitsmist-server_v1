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
//	Service initializer class
// =============================================================================

class ServiceInitializer extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$container = $request->getAttribute("container");
		$type = $this->getOption("type", "services");
		$options = $container["settings"][$type] ?? array();

		// Set default class if none is set
		if (!isset($options["className"]) && !isset($options["class"]))
		{
			$options["className"] = "Bitsmist\\v1\Services\PluginService";
		}

		$container[$type] = Util::resolveInstance($options, $type, $options, $container);

		return $handler->handle($request);

	}

}
