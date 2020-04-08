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

namespace Bitsmist\v1\Plugins\Base;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Plugin base class.
 */
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

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Set logger.
	 *
	 * @param	$logger			Logger.
	 */
	public function setLogger($logger)
	{

		$this->logger = $logger;

	}

}

