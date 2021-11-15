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

namespace Bitsmist\v1\Middlewares\Exception;

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Logging exception handler class
// =============================================================================

class LoggingExceptionHandler extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	private $messages = array();

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$exception = $request->getAttribute("exception");

		switch (get_class($exception))
		{
		case "Bitsmist\\v1\Exception\HttpException":
			break;
		default:
			$this->add("code = " . $exception->getCode() . ", message = ". $exception->getMessage() . ", file = " . $exception->getFile() . ", lineno = " . $exception->getLine());
			$this->add($exception->getTraceAsString());
			break;
		}

		$this->dumpLog();

	}

	// -------------------------------------------------------------------------

	/**
	 * Clear messages.
	 */
	public function clear()
	{

		$this->messages = array();

	}

    // -------------------------------------------------------------------------

	/**
	 * Add messages.
	 *
	 * @param	$msg			Messages.
	 * @param	$title1			Title1.
	 * @param	$title2			Title2.
	 */
	public function add($msg, ?string $title1 = "", ?string $title2 = "")
	{

    	if (is_array($msg)) {
    		$msgs = $msg;
    	} else {
	    	str_replace("\r\n", "\n", $msg);
	    	$msgs = explode("\n", $msg);
    	}

    	$title1 .= ( $title1 ? " " : "" );
    	$title2 .= ( $title2 ? " " : "" );

    	for ($i = 0; $i < count($msgs); $i++) {
			array_push($this->messages, $title1 . $title2 . $msgs[$i]);
		}

	}

    // -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
	 * Log error messages with logger
	 */
	private function dumpLog()
	{

		if ($this->logger)
		{
			for ($i = 0; $i < count($this->messages); $i++)
			{
				$this->logger->error("Exception: {message}", ["message"=>$this->messages[$i]]);
			}
			$this->clear();
		}

	}

}
