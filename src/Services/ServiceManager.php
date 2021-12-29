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

use Bitsmist\v1\Services\PluginService;
use Bitsmist\v1\Utils\Util;
use Pimple\Container;

// =============================================================================
//	Service manager class
// =============================================================================

class ServiceManager extends PluginService
{

	/**
	 * Get a service.
	 *
	 * @param	$serviceName	Service nam.
	 *
	 * @return	Service.
	 */
	public function get($serviceName)
	{

		if (!$this->plugins->offsetExists($serviceName))
		{
			$options1 = $this->container["settings"][$serviceName] ?? array();
			$options2 = $this->container["settings"]["services"]["uses"][$serviceName] ?? array();
			$options = array_merge($options1, $options2);

			$this->add($serviceName, $options);
		}

		return $this->plugins[$serviceName];

	}

}
