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

if (PHP_VERSION_ID < 80100)
{
	require __DIR__ . "/ServiceManager7.php";
}
else
{
	require __DIR__ . "/ServiceManager8.php";
}
