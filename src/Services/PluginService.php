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

use Bitsmist\v1\Util\Util;

// =============================================================================
//	Plugin service class
// =============================================================================

class PluginService
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
	 * Options.
	 *
	 * @var		array
	 */
	protected $options = null;

	/**
	 * Plugins.
	 *
	 * @var		array
	 */
	protected $plugins = array();

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
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
	 * Get plugins.
	 *
	 * @return	Plugins.
	 */
	public function getPlugins(): array
	{

		return $this->plugins;

	}

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

		// Merge settings
		$options = array_merge($this->container["spec"][$title] ?? array(), $options ?? array());

		// Create an instance
		$this->plugins[$title] = Util::resolveInstance($options, $this->container, $options);

		return $this->plugins[$title];

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

		foreach ($this->plugins as $pluginName => $plugin)
		{
			$ret[] = call_user_func_array(array($plugin, $name), $args);
		}

		return $ret;

	}

}
