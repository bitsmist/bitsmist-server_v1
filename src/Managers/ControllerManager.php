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
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Controller manager class.
 */
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
				$setting = $this->container["appInfo"]["spec"][$middlewareName];
				$setting = $this->container["loader"]->mergeArray($setting, $options);
				$this->handlers[$eventName]->add($middlewareName, $setting);
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
		$request = $request->withAttribute("loader", $this->container["loader"]);
		$request = $request->withAttribute("databases", $this->container["dbManager"]);
		$request = $request->withAttribute("loggers", $this->container["loggerManager"]);
		$request = $request->withAttribute("resultCode", HttpException::ERRNO_NONE);
		$request = $request->withAttribute("resultMessage", HttpException::ERRMSG_NONE);
		$request = $request->withAttribute("queryParams", $request->getQueryParams());

		foreach ($this->handlers as $eventName => $manager)
		{
			list($request, $response) = $manager->process($request, $response);
		}

		return $response;

	}

}
