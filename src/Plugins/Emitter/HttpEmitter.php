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

namespace Bitsmist\v1\Plugins\Emitter;

use Bitsmist\v1\Plugins\Base\PluginBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\HttpHandlerRunner\Emitter\SapiEmitter;

// =============================================================================
//	HTTP emitter class
// =============================================================================

class HttpEmitter extends PluginBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function emit(ResponseInterface $response)
	{

		$emitter = new SapiEmitter();
		$emitter->emit($response);

	}

}
