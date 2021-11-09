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

namespace Bitsmist\v1\Manager;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Middlware manager class.
 */
class MiddlewareManager
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Middlewares.
	 *
	 * @var		array
	 */
	protected $middlewares = array();

	/**
	 * Container.
	 *
	 * @var		Container
	 */
	protected $container = null;

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	$container		Container.
	 * @param	$settings		Middleware settings.
	 */
	//public function __construct(ContainerInterface $container, array $settings = null)
	public function __construct($container, array $settings = null)
	{

		$this->container = $container;

		if (is_array($settings)){
			foreach ($settings as $title => $options)
			{
				$this->add($title, $options);
			}
		}

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Create a middleware.
	 *
	 * @param	$options		Middleware options.
	 *
	 * @return	Created middleware.
	 */
	public function create(?array $options = null): MiddlewareBase
	{

		$className = $options["className"] ?? null;
		$middleware = new $className($options);
		if (method_exists($middleware, "setLogger"))
		{
			$middleware->setLogger($this->container["loggerManager"]);
		}

		return $middleware;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get middlewares.
	 *
	 * @return	Middlewares.
	 */
	public function getMiddlewares(): array
	{

		return $this->middlewares;

	}

	// -------------------------------------------------------------------------

	/**
	 * Add a middleware.
	 *
	 * @param	$title			Middleware name.
	 * @param	$options		Middleware options.
	 *
	 * @return	Added middleware.
	 */
	public function add(string $title, ?array $options): MiddlewareBase
	{

		$middleware = $this->create($options);

		if ($middleware)
		{
			$this->middlewares[$title] = $middleware;
		}

		return $middleware;

	}

	// -------------------------------------------------------------------------

	/**
	 * Process middleware chains.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Response.
	 */
	public function process(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{

		foreach ($this->middlewares as $middlewareName => $middleware)
		{
			$ret = null;

			if (is_callable($middleware))
			{
				$ret = $middleware($request, $response);
			}

			if ($ret instanceof \Psr\Http\Message\RequestInterface)
			{
				$request = $ret;
			}

			if ($ret instanceof \Psr\Http\Message\ResponseInterface)
			{
				$response = $ret;
			}
		}

		// Save request to container
		unset($this->container["request"]);
		$this->container["request"] = $request;

		// Save response to container
		unset($this->container["response"]);
		$this->container["response"] = $response;

		return $response;

	}

	// -------------------------------------------------------------------------

	/**
	 * Process middleware chains.
	 *
	 * @param	$request		Request.
	 * @param	$response		Response.
	 *
	 * @return	Response.
	 */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{

		return $this->process($request, $response);

	}

}

