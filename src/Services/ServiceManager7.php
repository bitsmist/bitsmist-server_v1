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

namespace Bitsmist\v1\Services;

use Bitsmist\v1\Utils\Util;
use Pimple\Container;

// =============================================================================
//	Service manager class
// =============================================================================

class ServiceManager implements \ArrayAccess
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Container.
	 *
	 * @var		Container
	 */
	protected $container = null;

	/**
	 * Service names.
	 *
	 * @var		array
	 */
	protected $serviceNames = array();

	/**
	 * Service container.
	 *
	 * @var		Container
	 */
	protected $services = null;

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
		$this->services = new Container();

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function offsetSet($offset, $value): void
	{

		$this->services->offsetSet($offset, $value);

    }

	// -------------------------------------------------------------------------

	public function offsetExists($offset): bool
	{

		return $this->services->offsetExists($offset);

    }

	// -------------------------------------------------------------------------

	public function offsetUnset($offset): void
	{

		$this->services->offsetUnset($offset);
		unset($this->serviceNames[$offset]);

    }

	// -------------------------------------------------------------------------

	public function offsetGet($offset)
	{

		return $this->get($offset);

	}

	// -------------------------------------------------------------------------

	/**
	 * Get a service.
	 *
	 * @param	$serviceName	Service nam.
	 *
	 * @return	Service.
	 */
	public function get($serviceName)
	{

		if (!array_key_exists($serviceName, $this->serviceNames))
		{
			$this->serviceNames[$serviceName] = true;
			$this->services[$serviceName] = function ($c) use ($serviceName) {
				// Merge settings
				$options1 = $this->container["settings"][$serviceName] ?? array();
				$options2 = $this->container["settings"]["services"]["uses"][$serviceName] ?? array();
				$options = array_merge($options1, $options2);

				// Get instance
				return Util::resolveInstance($options, $serviceName, $options, $this->container);
			};
		}

		return $this->services[$serviceName];

	}

}
