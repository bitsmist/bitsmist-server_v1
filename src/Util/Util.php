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

// =============================================================================
//	Utility class
// =============================================================================

class Util extends PluginBase
{

	// -------------------------------------------------------------------------
	//	Static
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
