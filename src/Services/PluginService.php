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
				$title = $value;
				$pluginOptions = null;
			}
			else
			{
				// Has options
				$title = $key;
				$pluginOptions = $value;
			}

			$this->add($title, $pluginOptions);
		}

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Add a plugin.
	 *
	 * @param	$title			Plugin name.
	 * @param	$options		Plugin options.
	 *
	 * @return	Added plugin.
	 */
	public function add(string $title, ?array $options)
	{

		$this->plugins[$title] = function ($c) use ($title, $options) {
			try
			{
				// Merge settings
				$options = array_merge($this->container["settings"][$title] ?? array(), $options ?? array());

				// Get instance
				return Util::resolveInstance($options, $title, $options, $this->container);
			}
			catch (\Throwable $e)
			{
				throw new \RuntimeException("Failed to create a plugin. pluginName=" . $title . ", reason=" . $e->getMessage());
			}
		};

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
	 * @param	$name			Method name.
	 * @param	$args			Arguments to the method.
	 *
	 * @return	Method resuls.
	 */
	public function __call(string $name, ?array $args): array
	{

		$ret = array();

		foreach ($this->plugins->keys() as $pluginName)
		{
			$plugin = $this->plugins[$pluginName];
			$ret[] = call_user_func_array(array($plugin, $name), $args);
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
