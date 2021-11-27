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

	public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
	{

		$options = $this->loader->getAppInfo("spec")["options"];

		// Check query parameters
		$allowedList = $options["query"]["parameters"] ?? null;
		if ($allowedList)
		{
			$allowedList = $this->alignArray($allowedList);
			$this->checkMissing($request->getQueryParams(), $allowedList);
			$this->checkValidity($request->getQueryParams(), $allowedList);
		}

		// Check body parameters
		$allowedList = $options["body"]["parameters"] ?? null;
		if ($allowedList)
		{
			$allowedList = $this->alignArray($allowedList);
			$itemsParamName = $options["body"]["specialParameters"]["items"] ?? null;
			$itemParamName = $options["body"]["specialParameters"]["item"] ?? null;

			// Get items
			if ($itemParamName)
			{
				$posts = array(($request->getParsedBody())[$itemParamName] ?? null);
			}
			else if ($itemsParamName)
			{
				$posts = ($request->getParsedBody())[$itemsParamName] ?? null;
			}
			else
			{
				$posts = array($request->getParsedBody());
			}

			foreach ((array)$posts as $item)
			{
				$this->checkMissing($item, $allowedList);
				$this->checkValidity($item, $allowedList);
			}
		}

	}

	// -------------------------------------------------------------------------
	//	Private
	// -------------------------------------------------------------------------

	/**
  	 * Convert an indexed array to an associative array.
	 *
	 * @param	$target			Array to convert.
	 *
	 * @return	string			Converted array..
     */
	private function alignArray($target): array
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
	private function checkMissing(?array $target, array $allowedList)
	{

		foreach ($allowedList as $key => $value)
		{
			$validations = $allowedList[$key]["validator"] ?? [];
			if (in_array("REQUIRED", $validations) && !array_key_exists($key, $target))
			{
				$this->loader->getService("logger")->alert("Parameter is missing: parameter = {key}, method = {httpmethod}, resource = {resource}", [
					"method" => __METHOD__,
					"key" => $key,
					"httpmethod" => $_SERVER['REQUEST_METHOD'],
					"resource" => $this->loader->getRouteInfo("args")["resource"],
				]);

				throw new HttpException(HttpException::ERRNO_PARAMETER, HttpException::ERRMSG_PARAMETER);
			}
		}

	}

	// -------------------------------------------------------------------------

	/**
  	 * Check parameters against white list.
	 *
	 * @param	$appInfo		Application information.
	 * @param	$method			Method.
	 * @param	$resource		Resource.
	 *
	 * @throws	HttpException
     */
	private function checkValidity(?array $target, array $allowedList)
	{

		foreach ((array)$target as $key => $value)
		{
			// Check whether a parameter is in the allowed list
			if (!array_key_exists($key, $allowedList))
			{
				$this->loader->getService("logger")->alert("Invaild parameter: parameter = {key}, value = {value}, method = {httpmethod}, resource = {resource}", [
					"method" => __METHOD__,
					"key" => $key,
					"value" => $value,
					"httpmethod" => $_SERVER['REQUEST_METHOD'],
					"resource" => $this->loader->getRouteInfo("args")["resource"],
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
