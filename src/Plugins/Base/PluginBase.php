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
	 * Properties.
	 *
	 * @var		array
	 */
	protected $props = array();

	/**
	 * Loader.
	 *
	 * @var		Loader
	 */
	protected $loader = null;

	/**
	 * Logger.
	 *
	 * @var		Logger
	 */
	protected $logger = null;

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
	 * @param	options			Plugin options.
	 */
	public function __construct(?array $options)
	{

		$this->options = $options;
		$this->loader = $options["loader"] ?? null;
		$this->logger = $options["logger"] ?? null;

	}

}
