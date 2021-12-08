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

namespace Bitsmist\v1\Utils;

use Bitsmist\v1\Plugins\Base\PluginBase;

// =============================================================================
//	Formatter utility class
// =============================================================================

class FormatterUtil extends PluginBase
{

	// -------------------------------------------------------------------------
	//	Static
	// -------------------------------------------------------------------------

	/**
	 * Format the value.
	 *
	 * @param	$value			Value to format.
	 * @param	$type			Format type.
	 * @param	$format			Format.
	 *
	 * @return	Formatted value.
	 */
	static public function format($value, ?string $type, ?string $format)
	{

		$ret = $value;

		if ($value && $type && $format)
		{
			switch ($type)
			{
			case "date":
				$ret = date($format, strtotime($value));
				break;
			}
		}

		return $ret;

	}

}
