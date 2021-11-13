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

namespace Bitsmist\v1\Managers;

use Bitsmist\v1\Plugins\Base\PluginBase;
use Psr\Container\ContainerInterface;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Plugin manager class.
 */
class PluginManager
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Plugins.
	 *
	 * @var		array
	 */
	protected $plugins = array();

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

		if (is_array($options["uses"])){
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
	 * Create a plugin.
	 *
	 * @param	$options		Plugin options.
	 *
	 * @return	Created plugin.
	 */
	public function create(?array $options = null)
	{

		// Create a plugin
		$className = $options["className"] ?? null;
		$plugin = new $className($options);

		// Set loggers
		if (method_exists($plugin, "setLogger"))
		{
			$plugin->setLogger($this->container["loggerManager"]);
		}

		return $plugin;

	}

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
		$options["container"] = $this->container;
		$setting = $this->container["appInfo"]["spec"][$title];
		if ($options)
		{
			$setting = array_merge($setting, $options);
		}

		$this->plugins[$title] = $this->create($setting);

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

