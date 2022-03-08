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
use Bitsmist\v1\Utils\BitsmistContainer;
use Pimple\Container;

// =============================================================================
//	Plugin service class
// =============================================================================

class PluginService implements \ArrayAccess, \Countable, \IteratorAggregate
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Name.
	 *
	 * @var		string
	 */
	protected $name = "";

	/**
	 * Container.
	 *
	 * @var		Container
	 */
	protected $container = null;

	/**
	 * Options.
	 *
	 * @var		array
	 */
	protected $options = null;

	/**
	 * Plugins.
	 *
	 * @var		Container.
	 */
	protected $plugins = null;

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	$name			Plugin name.
	 * @param	$options		Options.
	 * @param	$container		Container.
	 */
	public function __construct(string $name, array $options = null, $container)
	{

		$this->container = $container;
		$this->options = $options;
		$this->plugins = new Container();

		foreach ((array)($this->options["uses"] ?? null) as $key => $value)
		{
			if (is_numeric($key))
			{
				// Does not have options
				$plugin = $value;
				$pluginOptions = null;
			}
			else
			{
				// Has options
				$plugin = $key;
				$pluginOptions = $value;
			}

			$this->add($plugin, $pluginOptions);
		}

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Add a plugin.
	 *
	 * @param	$plugin			Plugin name or plugin object.
	 * @param	$options		Plugin options.
	 */
	public function add($plugin, ?array $options)
	{

		if (is_string($plugin))
		{
			$this->plugins[$plugin] = function ($c) use ($plugin, $options) {
				try
				{
					// Merge settings
					$options = array_merge($this->container["settings"][$plugin] ?? array(), $options ?? array());

					// Get instance
					return Util::resolveInstance($options, $plugin, $options, $this->container);
				}
				catch (\Throwable $e)
				{
					throw new \RuntimeException("Failed to create a plugin. pluginName=" . $plugin . ", reason=" . $e->getMessage());
				}
			};
		}
		else
		{
			$hash = spl_object_hash($plugin);
			$this->plugins[$hash] = $plugin;
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Get a plugin.
	 *
	 * @param	$pluginName		Plugin name.
	 *
	 * @return	Object.
	 */
	public function get($pluginName)
	{

		return $this->plugins[$pluginName];

	}

	// -------------------------------------------------------------------------

	/**
	 * Call the plugin method.
	 *
	 * @param	$methodName		Method name.
	 * @param	$args			Arguments to the method.
	 *
	 * @return	Method resuls.
	 */
	public function __call(string $methodName, ?array $args): array
	{

		$ret = array();

		foreach ($this->plugins->keys() as $pluginName)
		{
			$plugin = $this->plugins[$pluginName];

			// Check if the plugin has method
			if (!method_exists($plugin, $methodName))
			{
				$msg = sprintf("Plugin does not have a method. pluginName=%s, methodName=%s", $pluginName, $methodName);
				throw new \RunTimeException($msg);
			}

			// Execute
			$ret[] = call_user_func_array(array($plugin, $methodName), $args);
		}

		return $ret;

	}
	// -------------------------------------------------------------------------

	public function count(): int
	{

		return count($this->plugins->keys());

	}

	// -------------------------------------------------------------------------

	public function getIterator(): \Traversable
	{

		foreach ($this->plugins->keys() as $key)
		{
			yield $key => $this->plugins[$key];
		}

	}

	// -------------------------------------------------------------------------

	public function offsetExists($offset): bool
	{

		return $this->plugins->offsetExists($offset);

    }

	// -------------------------------------------------------------------------

	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{

		return $this->get($offset);

	}

	// -------------------------------------------------------------------------

	public function offsetSet($offset, $value): void
	{

		$this->plugins->offsetSet($offset, $value);

    }

	// -------------------------------------------------------------------------

	public function offsetUnset($offset): void
	{

		$this->plugins->offsetUnset($offset);

    }

}
