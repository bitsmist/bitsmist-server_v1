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

use Bitsmist\v1\Exception\HttpException;
use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
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
		$allowedList = $this->alignArray($allowedList);
		$this->checkMissing($request, $request->getQueryParams(), $allowedList);
		$this->checkValidity($request, $request->getQueryParams(), $allowedList);

		// Check body parameters
		$allowedList = $options["body"]["parameters"] ?? array();
		$allowedList = $this->alignArray($allowedList);
		$items = $this->getParamsFromBody($request, $options);
		foreach ((array)$items as $item)
		{
			$this->checkMissing($request, $item, $allowedList);
			$this->checkValidity($request, $item, $allowedList);
		}

		return $handler->handle($request);

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
  	 * Convert an indexed array to an associative array.
	 *
	 * @param	$target			Array to convert.
	 *
	 * @return	array			Converted array.
     */
	private function alignArray(array $target): array
	{

		$result = array();

		foreach ($target as $key => $value)
		{
			if (is_numeric($key))
			{
				$key = $value;
				$value = null;
			}

			$result[$key] = $value;
		}

		return $result;

	}

	// -------------------------------------------------------------------------

	/**
  	 * Get parameter arrays from body.
	 *
	 * @param	$request		Request object.
	 * @param	$options		Options.
	 *
	 * @return	array			Parameter arrays.
     */
	private function getParamsFromBody(ServerRequestInterface $request, $options)
	{

		$itemsParamName = $options["body"]["specialParameters"]["items"] ?? null;
		$itemParamName = $options["body"]["specialParameters"]["item"] ?? null;

		if ($itemParamName)
		{
			$items = array(($request->getParsedBody())[$itemParamName] ?? null);
		}
		else if ($itemsParamName)
		{
			$items = ($request->getParsedBody())[$itemsParamName] ?? null;
		}
		else
		{
			$items = array($request->getParsedBody());
		}

		return $items;

	}

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
	private function checkMissing(ServerRequestInterface $request, ?array $target, array $allowedList)
	{

		foreach ($allowedList as $key => $value)
		{
			$validations = $allowedList[$key]["validator"] ?? [];
			if (in_array("REQUIRED", $validations) && !array_key_exists($key, $target))
			{
				$request->getAttribute("services")["logger"]->alert("Parameter is missing: parameter = {key}", [
					"method" => __METHOD__,
					"key" => $key,
				]);

				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
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
	private function checkValidity(ServerRequestInterface $request, ?array $target, array $allowedList)
	{

		$ignoreExtraParams = $this->getOption("ignoreExtraParams", false);

		foreach ((array)$target as $key => $value)
		{
			// Check whether a parameter is in the list
			if (!$ignoreExtraParams && !array_key_exists($key, $allowedList))
			{
				$request->getAttribute("services")["logger"]->alert("Invaild parameter: parameter = {key}, value = {value}", [
					"method" => __METHOD__,
					"key" => $key,
					"value" => $value,
				]);

				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
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
