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

namespace Bitsmist\v1\Plugins\Logger;

use Bitsmist\v1\Plugins\Base\PluginBase;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Base Logger class.
 */
class BaseLogger extends PluginBase
{

	use LoggerTrait;

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	/**
	 * Log file name.
	 *
	 * @var		string
	 */
	public $fileName = "";

	/**
	 * Log level.
	 *
	 * @var		string
	 */
	public $level = LogLevel::ERROR;

	/**
	 * Log level priorities.
	 *
	 * @var		string
	 */
	protected $priority = array(
		"emergency"		=> 8,
		"alert"			=> 7,
		"critical"		=> 6,
		"error"			=> 5,
		"warning"		=> 4,
		"notice"		=> 3,
		"info"			=> 2,
		"debug"			=> 1,
	);

	// -------------------------------------------------------------------------
	//	Constructor, Destructor
	// -------------------------------------------------------------------------

	public function __construct($container, ?array $options)
	{

		parent::__construct($container, $options);

		$this->level = $options["level"] ?? "info";
		$this->fileName = $options["baseDir"] . basename($options["fileName"]);

	}

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

    /**
     * Log the message.
     *
     * @param	$level
     * @param	$message
     * @param	$context
     */
    public function log($level, $message, array $context = array())
	{
	}

	// -------------------------------------------------------------------------

	/**
	 * Convert an object to string with var_dump().
	 *
	 * @param	$target			Object to be converted.
	 *
	 * @return	Converted object.
	 */
	public function dumpObject($target)
	{

		ob_start();
		var_dump($target);
		$str =rtrim(ob_get_contents());
		ob_end_clean();

		return $str;

	}

}
