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

namespace Bitsmist\v1\Middlewares\Validator;

use Bitsmist\v1\Exceptions\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Utils\Util;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Parameter validator class
// =============================================================================

class ParameterValidator extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$options = $request->getAttribute("settings")["options"];

		// Check query parameters
		$allowedList = $options["query"]["parameters"] ?? array();
		$allowedList = Util::convertToAssocArray($allowedList);
		$this->checkMissing($request, $request->getQueryParams(), $allowedList, "query");
		$this->checkValidity($request, $request->getQueryParams(), $allowedList, "query");

		// Check body parameters
		$allowedList = $options["body"]["parameters"] ?? array();
		$allowedList = Util::convertToAssocArray($allowedList);
		$items = Util::getItemsFromBody($request, $options);
		foreach ((array)$items as $item)
		{
			$this->checkMissing($request, $item, $allowedList, "body");
			$this->checkValidity($request, $item, $allowedList, "body");
		}

		return $handler->handle($request);

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
  	 * Check if required parameters are missing.
	 *
	 * @param	$appInfo		Application information.
	 * @param	$method			Method.
	 * @param	$resource		Resource.
	 *
	 * @return	Record count.
	 *
	 * @throws	HttpException
     */
	private function checkMissing(ServerRequestInterface $request, ?array $target, array $allowedList, $type)
	{

		foreach ($allowedList as $key => $value)
		{
			$validations = $allowedList[$key]["validator"] ?? [];
			if (in_array("REQUIRED", $validations) && !array_key_exists($key, $target))
			{
				$msg = sprintf("Missing %s parameter. parameter=%s, method=%s, resource=%s",
					$type,
					$key,
					$request->getMethod(),
					$request->getAttribute("routeInfo")["args"]["resource"] ?? ""
				);

				$request->getAttribute("services")["logger"]->alert("{msg}", ["method" => __METHOD__, "msg" => $msg]);

				$e = new HttpException(HttpException::ERRMSG_PARAMETER, HttpException::ERRNO_PARAMETER);
				$e->setDetailMessage($msg);
				throw $e;
			}
		}

	}

	// -------------------------------------------------------------------------

	/**
  	 * Check parameters against the list.
	 *
	 * @param	$appInfo		Application information.
	 * @param	$method			Method.
	 * @param	$resource		Resource.
	 *
	 * @throws	HttpException
     */
	private function checkValidity(ServerRequestInterface $request, ?array $target, array $allowedList, $type)
	{

		$ignoreExtraParams = $this->getOption("ignoreExtraParams", false);

		foreach ((array)$target as $key => $value)
		{
			// Check whether a parameter is in the list
			if (!$ignoreExtraParams && !array_key_exists($key, $allowedList))
			{
				$msg = sprintf("Invaild %s parameter. parameter=%s, method=%s, resource=%s",
					$type,
					$key,
					$request->getMethod(),
					$request->getAttribute("routeInfo")["args"]["resource"] ?? ""
				);

				$request->getAttribute("services")["logger"]->alert("{msg}", ["method" => __METHOD__, "msg" => $msg]);

				$e = new HttpException(HttpException::ERRMSG_PARAMETER, HttpException::ERRNO_PARAMETER);
				$e->setDetailMessage($msg);
				throw $e;
			}

			// Validate a parameter
			$validations = $allowedList[$key]["validator"] ?? [];
			for ($i = 0; $i < count($validations); $i++)
			{
				$this->validate($target[$key], $validations[$i]);
			}
		}

	}

	private function validate($value, $validation)
	{
	}

}
