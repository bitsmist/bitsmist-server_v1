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

namespace Bitsmist\v1\Plugins\Controller;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Manager\MiddlewareManager;
use Bitsmist\v1\Plugins\Base\PluginBase;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Eventdriven controller class.
 */
class EventController extends PluginBase implements RequestHandlerInterface
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
	 * Middleware manager.
	 *
	 * @var		array
	 */
	private $handlers = array();

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param	$options		Options.
	 */
	public function __construct(array $options)
	{

		$this->container = $options["container"];
		$this->options = $options;

		// Load event handlers
		foreach ($options["events"] as $eventName => $spec)
		{
			$this->handlers[$eventName] = new MiddlewareManager($this->container);
			$this->container["loader"]->loadMiddlewares($eventName, $this->handlers[$eventName], $options["events"]);
		}

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Handle the api request.
	 *
	 * @param	$request		Request.
	 *
	 * @return	Response.
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{

		ini_set("session.cookie_httponly", TRUE);

		$request = $request->withAttribute("appInfo", $this->container["appInfo"]);
		$request = $request->withAttribute("loader", $this->container["loader"]);
		$request = $request->withAttribute("databases", $this->container["dbManager"]);
		$request = $request->withAttribute("loggers", $this->container["loggerManager"]);
		$request = $request->withAttribute("resultCode", HttpException::ERRNO_NONE);
		$request = $request->withAttribute("resultMessage", HttpException::ERRMSG_NONE);
		$request = $request->withAttribute("queryParams", $request->getQueryParams());

		/*
		$container = new Container();
		$container["appInfo"] = $this->container["appInfo"];
		$container["loader"] = $this->container["loader"];
		$container["databases"] = $this->container["dbManager"];
		 */
		$response = $this->container["response"];

		foreach ($this->handlers as $eventName => $manager)
		{
			$manager->process($request, $response);

			$request = $this->container["request"];
			$response = $this->container["response"];
		}

		return $response;

	}

}

