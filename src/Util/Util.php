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

namespace Bitsmist\v1\Util;

use Bitsmist\v1\Plugins\Base\PluginBase;
use Psr\Http\Message\ResponseInterface;

// =============================================================================
//	Utility class
// =============================================================================

class Util extends PluginBase
{

	// -------------------------------------------------------------------------
	//	Static
	// -------------------------------------------------------------------------

	/**
	 * Set php.ini.
	 *
	 * @param	$options		Options.
	 */
	static public function setIni(?array $options)
	{

		foreach ((array)$options as $key => $value)
		{
			ini_set($key, $value);
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Instantiate a class from options.
	 *
	 * @param	$options		Options.
	 * @param	...$args		Arguments to constructor.
	 *
	 * @return	Instance.
	 */
	static public function resolveInstance($options, ...$args)
	{

		$obj = null;

		if (isset($options["className"]))
		{
			$className = $options["className"];
			$obj = new $className(...$args);
		}
		else if (isset($options["class"]))
		{
			$obj = $options["class"];
		}

		return $obj;

	}

}
