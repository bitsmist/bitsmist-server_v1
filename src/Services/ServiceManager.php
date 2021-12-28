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

namespace Bitsmist\v1\Services;

use Bitsmist\v1\Services\ServiceManagerBase;

if (PHP_VERSION_ID < 80100)
{
	class ServiceManager extends ServiceManagerBase implements \ArrayAccess {};
}
else
{
	class ServiceManager extends ServiceManagerBase implements \ArrayAccess
	{

		public function offsetGet($offset): mixed
		{

			return $this->get($offset);

		}

	}
}
