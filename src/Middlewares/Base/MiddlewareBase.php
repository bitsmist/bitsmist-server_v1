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

namespace Bitsmist\v1\Middlewares\Base;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Middleware base class
// =============================================================================

abstract class MiddlewareBase implements MiddlewareInterface
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Name.
	 *
	 * @var		string
	 */
	protected $name = "";

	/**
	 * Options.
	 *
	 * @var		array
	 */
	protected $options = null;

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	string		$name				Middleware name.
	 * @param	?array		$options			Middleware options.
	 */
	public function __construct($name, ?array $options)
	{

		$this->name = $name;
		$this->options = $options;

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Get a specified middleware option.
	 *
	 * @param	string		$optionsName		Option name to get.
	 * @param	object		$default			A value to return when no option is set.
	 *
	 * @return	Object
	 */
	public function getOption(string $optionName, $default = null)
	{

		return $this->options[$optionName] ?? $default;

	}

	// -------------------------------------------------------------------------

	/**
	 * Handle a request.
	 *
	 * @param	RequestHandlerInterface	$request	Request.
	 * @param	RequestHandlerInterface	$handler	Handler.
	 *
	 * @return	ResponseInterface
	 */
	abstract public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;

	// -------------------------------------------------------------------------

	/**
	 * Handle a request by calling process method.
	 *
	 * @param	RequestHandlerInterface	$request	Request.
	 * @param	RequestHandlerInterface	$handler	Handler.
	 *
	 * @return	ResponseInterface
	 */
	public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		return $this->process($request, $handler);

	}

}
