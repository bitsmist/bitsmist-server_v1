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

// =============================================================================
//	Variable Store Utility class
// =============================================================================

class VarStoreUtil implements \ArrayAccess
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Variables.
	 *
	 * @var	array
	 */
	private $vars = array();

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param	$settings		Settings.
	 */
	public function __construct(?array $vars = null)
	{

		if (is_array($vars))
		{
			$this->merge($vars);
		}

	}

	// -------------------------------------------------------------------------
	//	public
	// -------------------------------------------------------------------------

	/**
	 * Add/Update a variable.
	 *
	 * @param	$key			Key.
	 * @param	$value			Value.
	 */
	public function set(string $key, $value)
	{

		$this->vars[$key] = $value;

	}

	// -------------------------------------------------------------------------

	/**
	 * Remove a variable.
	 *
	 * @param	$key			Key.
	 */
	public function remove(string $key)
	{

		unset($this->vars[$key]);

	}

	// -------------------------------------------------------------------------

	/**
	 * Merge variables array.
	 *
	 * @param	$vars			Array of vars.
	 */
	public function merge(array $vars)
	{

		$this->vars = array_merge($this->vars, $vars);

	}

	// -------------------------------------------------------------------------

	/**
  	 * Replace variables in target strings.
	 *
	 * @param	$targets		Target (array of) strings.
	 * @param	$dics			Additional dictionaries.
	 *
	 * @return	Replaced strings.
     */
	public function replace($targets, ?array $dics = null)
	{

		$from = array();
		$to = array();

		foreach ([$this->vars, (array)$dics] as $dic)
		{
			$keys = array_map(function($x){return "{" . $x . "}";}, array_keys($dic));
			$from = array_merge($from, $keys);
			$to = array_merge($to, array_values($dic));
		}

		return str_replace($from, $to, $targets ?? "");

	}

	// -------------------------------------------------------------------------

	public function offsetExists($offset): bool
	{

		return array_key_exists($offset, $this->vars);

    }

	// -------------------------------------------------------------------------

	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{

		return $this->vars[$offset];

	}

	// -------------------------------------------------------------------------

	public function offsetSet($offset, $value): void
	{

		$this->vars[$offset] = $value;

    }

	// -------------------------------------------------------------------------

	public function offsetUnset($offset): void
	{

		unset($this->vars[$offset]);

    }

}
