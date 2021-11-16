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

namespace Bitsmist\v1\Managers;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Managers\MiddlewareManager;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Controller manager class
// =============================================================================

class ControllerManager
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Container.
	 *
	 * @var		Container
	 */
	private $container = null;

	/**
	 * Middleware managers.
	 *
	 * @var		array
	 */
	private $handlers = array();

	/**
	 * Options.
	 *
	 * @var		array
	 */
	protected $options = null;

	// -------------------------------------------------------------------------
	//	Constructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	$container		Container.
	 * @param	$options		Options.
	 */
	public function __construct($container, array $options = null)
	{

		$this->container = $container;
		$this->options = $options;

		// Load event handlers
		foreach ($options["events"] as $eventName => $spec)
		{
			$this->handlers[$eventName] = new MiddlewareManager($this->container, $spec);

			$specs = $options["events"][$eventName]["uses"] ?? array();
			foreach ($specs as $middlewareName => $options)
			{
				$middlewareOptions = $this->container["appInfo"]["spec"][$middlewareName];
				$middlewareOptions = $this->container["loader"]->mergeArray($middlewareOptions, $options);
				$middlewareOptions["logger"] = $this->container["loggerManager"];
				$middlewareOptions["loader"] = $this->container["loader"];

				$this->handlers[$eventName]->add($middlewareName, $middlewareOptions);
			}
		}

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Handle an request.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Response.
	 */
	public function handle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{

		ini_set("session.cookie_httponly", TRUE);

		$request = $request->withAttribute("appInfo", $this->container["appInfo"]);
		$request = $request->withAttribute("databases", $this->container["dbManager"]);
		$request = $request->withAttribute("resultCode", HttpException::ERRNO_NONE);
		$request = $request->withAttribute("resultMessage", HttpException::ERRMSG_NONE);

		foreach ($this->handlers as $eventName => $manager)
		{
			list($request, $response) = $manager->process($request, $response);
		}

		return $response;

	}

}
