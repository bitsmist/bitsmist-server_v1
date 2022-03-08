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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Utility class
// =============================================================================

class Util extends PluginBase
{

	// -------------------------------------------------------------------------
	//	public
	// -------------------------------------------------------------------------

	/**
	 * Set php.ini.
	 *
	 * @param	$options		Options.
	 */
	public static function setIni(?array $options)
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
	public static function resolveInstance($options, ...$args)
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
		else
		{
			throw new \RuntimeException("Can not resolve an instance because no class/className is specified.");
		}

		return $obj;

	}

	// -------------------------------------------------------------------------

	/**
  	 * Convert an indexed array to an associative array.
	 *
	 * @param	$target			Array to convert.
	 *
	 * @return	array			Converted array.
     */
	public static function convertToAssocArray(?array $target): array
	{

		$result = array();

		foreach ((array)$target as $key => $value)
		{
			if (is_int($key))
			{
				$key = $value;
				$value = null;
			}

			$result[$key] = $value;
		}

		return $result;

	}

	// -------------------------------------------------------------------------

	/**
  	 * Get items array from body.
	 *
	 * @param	$request		Request object.
	 * @param	$options		Options.
	 *
	 * @return	array			Parameter arrays.
     */
	public static function getItemsFromBody(ServerRequestInterface $request, $options)
	{

		$itemsParamName = $options["body"]["specialParameters"]["items"] ?? null;
		$itemParamName = $options["body"]["specialParameters"]["item"] ?? null;

		if ($itemParamName)
		{
			$items = array(($request->getParsedBody())[$itemParamName] ?? null);
		}
		else if ($itemsParamName)
		{
			$items = ($request->getParsedBody())[$itemsParamName] ?? null;
		}
		else
		{
			$items = array($request->getParsedBody());
		}

		return $items;

	}

}
