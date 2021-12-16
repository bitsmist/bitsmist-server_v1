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

namespace Bitsmist\v1\Middlewares\Formatter;

use Bitsmist\v1\Middlewares\Base\MiddlewareBase;
use Bitsmist\v1\Utils\FormatterUtil;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// =============================================================================
//	Query limiter class
// =============================================================================

class QueryLimiter extends MiddlewareBase
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{

		$gets = $request->getQueryParams();

		foreach ((array)($this->options["parameters"] ?? null) as $parameterName => $rules)
		{
			// Skip to next rule if the parameter doesn't exists in URL parameters
			if (!array_key_exists($parameterName, $gets))
			{
				continue;
			}

			// Check each rules for this parameter
			foreach ((array)$rules as $ruleName  => $ruleValue)
			{
				switch($ruleName)
				{
				case "min":
					if ($get[$parameterName] < $ruleValue)
					{
						$gets[$parameterName] = $ruleValue;
					}
					break;
				case "max":
					if ($gets[$parameterName] > $ruleValue)
					{
						$gets[$parameterName] = $ruleValue;
					}
					break;
				}
			}
		}

		$request = $request->withQueryParams($gets);

		return $handler->handle($request);
	 }

}
