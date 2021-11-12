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

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	$container		Container.
	 * @param	$settings		Middleware settings.
	 */
	//public function __construct(ContainerInterface $container, array $settings = null)
	public function __construct($container, array $settings = null)
	{

		$this->container = $container;

		if (is_array($settings["uses"])){
			foreach ($settings["uses"] as $key => $value)
			{
				if (is_array($value))
				{
					$title = $key;
					$options = $value;
				}
				else
				{
					$title = $value;
					$options = null;
				}

				$this->add($title, $options);
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

		$className = $options["className"] ?? null;
		$plugin = new $className($options);
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

		$options["container"] = $this->container;
		$setting = $this->container["appInfo"]["spec"][$title];
		if ($options)
		{
			$setting = array_merge($setting, $options);
		}

		$plugin = $this->create($setting);

		if ($plugin)
		{
			$this->plugins[$title] = $plugin;
		}

		return $plugin;

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

