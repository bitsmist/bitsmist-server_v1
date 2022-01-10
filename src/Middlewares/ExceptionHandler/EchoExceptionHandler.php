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

namespace Bitsmist\v1\Middlewares\ExceptionHandler;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Echo exception handler class
// =============================================================================

class EchoExceptionHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{


		$response = $handler->handle($request);

		// Add an error msg to response body
		$response->getBody()->write($response->getBody()->getContents() . "\n\n" . $this->getErrorMsg($request));

		return $response;

	}

	// -------------------------------------------------------------------------
	// 	Private
	// -------------------------------------------------------------------------

	/**
	 * Get an error message.
	 *
	 * @param	$request		Request object.
	 *
	 * @return	string.
	 */
	private function getErrorMsg($request)
	{

		$ex = $request->getAttribute("exception");
		$settings = $request->getAttribute("settings");
		$msg = "";

		if ($settings["options"]["show_htmlErrors"] ?? false)
		{
			$msg = "<b>Fatal error</b>: " . $ex->getMessage() . " in <b>" . $ex->getFile() . "</b> on line <b>". $ex->getLine() . "</b><br>\n" . $ex->getTraceAsString();
		}
		else
		{
			$msg = "Fatal error: " . $ex->getMessage() . " in " . $ex->getFile() . " on line ". $ex->getLine() . "\n" . $ex->getTraceAsString();
		}

		return $msg;

	}

}
