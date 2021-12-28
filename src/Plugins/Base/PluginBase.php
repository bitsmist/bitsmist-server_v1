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

namespace Bitsmist\v1\Plugins\Base;

// =============================================================================
//	Plugin base class
// =============================================================================

class PluginBase
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
	 * Properties.
	 *
	 * @var		array
	 */
	protected $props = array();

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	/**
	 * Constructor.
	 *
	 * @param	$name			Plugin name.
	 * @param	$options		Plugin options.
	 * @param	$container		Container.
	 */
	public function __construct($name, ?array $options, $container)
	{

		$this->name = $name;
		$this->options = $options;
		$this->container = $container;

	}

}
