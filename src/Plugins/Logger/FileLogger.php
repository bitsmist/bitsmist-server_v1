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

use Bitsmist\v1\Plugins\Logger\BaseLogger;

// -----------------------------------------------------------------------------
//	Class
// -----------------------------------------------------------------------------

/**
 * File logger class.
 */
class FileLogger extends BaseLogger
{

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

	    if ($this->priority[$level] >= $this->priority[$this->level]) {
			file_put_contents($this->fileName, $this->formatLine($level, $message, $context), FILE_APPEND);
	    }

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
	 * Format the message.
	 *
	 * @param	$level				Log level.
	 * @param	$methodName			Method name.
	 * @param	$msg				Message.
	 *
	 * @return	Formatted message.
	 */
	private function formatLine($level, $message, array $context = array())
	{

		$line = $message;

		foreach ($context as $key => $value)
		{
			$line = str_replace("{" . $key . "}", $value, $line);
		}
	    $line = date('Y/m/d H:i:s') . " " .$level . " " . $_SERVER["REMOTE_ADDR"] . " " . substr(session_id(), -8) . " " . ( array_key_exists("method", $context) ? $context["method"] . "() " : "" ) . $line . "\r\n";

		return $line;

	}

}
