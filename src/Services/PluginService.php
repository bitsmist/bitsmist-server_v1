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

// =============================================================================
//	Plugin service class
// =============================================================================

class PluginService
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Loader.
	 *
	 * @var		Loader
	 */
	protected $loader = null;

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
	 * @param	$loader			Loader.
	 * @param	$options		Options.
	 */
	public function __construct($loader, array $options = null)
	{

		$this->loader = $loader;
		$this->options = $options;

		if ($options["uses"] ?? null)
		{
			foreach ($options["uses"] as $key => $value)
			{
				if (is_array($value))
				{
					$title = $key;
					$pluginOptions = $value;
				}
				else
				{
					$title = $value;
					$pluginOptions = null;
				}

				$this->add($title, $pluginOptions);
			}
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
		$options = array_merge($this->loader->getAppInfo("spec")[$title] ?? array(), $options ?? array());

		// Create an instance
		$className = $options["className"] ?? null;
		$this->plugins[$title] = new $className($this->loader, $options);

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
