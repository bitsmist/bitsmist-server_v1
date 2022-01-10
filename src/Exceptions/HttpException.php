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

namespace Bitsmist\v1\Exceptions;

use Exception;

// =============================================================================
//	Http exception class
// =============================================================================

class HttpException extends Exception
{

	// -------------------------------------------------------------------------
	//	Constants, Variables
	// -------------------------------------------------------------------------

	// Error numbers.

	/** Error number for no error. */
	const ERRNO_NONE 							= 200;
	/** Error number for parameter error. */
	const ERRNO_PARAMETER  						= 400;
	/** Error number for not authorized error. */
	const ERRNO_PARAMETER_NOTAUTHORIZED			= 401;
	/** Error number for invalid resource error. */
	const ERRNO_PARAMETER_INVALIDRESOURCE		= 404;
	/** Error number for invalid route error. */
	const ERRNO_PARAMETER_INVALIDROUTE			= 404;
	/** Error number for invalid method error. */
	const ERRNO_PARAMETER_INVALIDMETHOD			= 405;
	/** Error number for invalid format error. */
	const ERRNO_PARAMETER_INVALIDFORMAT			= 406;
	/** Error number for exception. */
	const ERRNO_EXCEPTION						= 500;

	// Error messages

	/** Error message for no error. */
	CONST ERRMSG_NONE							= "";
	/** Error message for parameter error. */
	CONST ERRMSG_PARAMETER						= "Parameter error.";
	/** Error message for not authorized error. */
	CONST ERRMSG_PARAMETER_NOTAUTHORIZED		= "Not authorized.";
	/** Error message for invalid resource error. */
	CONST ERRMSG_PARAMETER_INVALIDRESOURCE		= "Invalid resource.";
	/** Error message for invalid route error. */
	CONST ERRMSG_PARAMETER_INVALIDROUTE			= "Invalid route.";
	/** Error message for invalid resource error. */
	CONST ERRMSG_PARAMETER_INVALIDMETHOD		= "Invalid method.";
	/** Error message for invalid format error. */
	CONST ERRMSG_PARAMETER_INVALIDFORMAT		= "Invalid format.";
	/** Error message for exception. */
	CONST ERRMSG_EXCEPTION						= "Exception occurred.";

	/**
	 * Detail message.
	 *
	 * @var		string
	 */
	protected string $detailMessage				= "";

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	/**
	 * Set detail message.
	 *
	 * @param	$msg			Detail message.
	 *
	 */
	public function setDetailMessage(string $msg)
	{

		$this->detailMessage = $msg;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get detail message.
	 *
	 * @return	string
	 */
	public function getDetailMessage(): ?string
	{

		return $this->detailMessage;

	}

}
