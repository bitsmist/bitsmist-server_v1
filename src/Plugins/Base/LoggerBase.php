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

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * Logger class.
 */
class LoggerBase extends AbstractLogger
{

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

	/**
	 * Constructor.
	 *
	 * @param	$options		Options.
	 */
	public function __construct(?array $options)
	{

		$this->options = $options;
		$this->level = $options["level"];
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

