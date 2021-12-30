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

namespace Bitsmist\v1\Plugins\DB;

use Bitsmist\v1\Plugins\DB\BaseDB;

// =============================================================================
//	Curl database class
// =============================================================================

abstract class CurlDB extends BaseDB
{

	// -------------------------------------------------------------------------
	//	Public
	// -------------------------------------------------------------------------

	public function open($setting = null)
	{

		$this->props["lastResponse"] = null;
		$this->props["connection"] = curl_init();

		$this->logger->debug(
			"dsn={dsn}, user={user}, password={password}", [
				"method" => __METHOD__,
				"dsn" => $this->props["dsn"],
				"user" => $this->props["user"],
				"password" => substr($this->props["password"] ?? "", 0, 1) . "*******"
			]
		);

		return $this->props["connection"];

	}

    // -------------------------------------------------------------------------

	public function close()
	{

		if ($this->props["connection"])
		{
			curl_close($this->props["connection"]);
			$this->props["connection"] = null;

			$this->logger->debug("dsn={dsn}", ["method"=>__METHOD__, "dsn"=>$this->props["dsn"]]);
		}

	}

    // -------------------------------------------------------------------------

	public function execute($cmd, $params = null)
	{

		$this->logger->info("method={httpmethod}, url={url}", ["method"=>__METHOD__, "httpmethod"=>$cmd["method"], "url"=>$cmd["url"]]);

		// Init curl
		$this->initCurl($cmd);

		// Exec
		$ret = curl_exec($this->props["connection"]);
		if (curl_errno($this->props["connection"]))
		{
			// Error
			$this->logger->error("curl_exec() failed: errno={errno}, message={message}, url={url}", [
				"method"=>__METHOD__,
				"errno"=> curl_errno($this->props["connection"]),
				"message"=>curl_error($this->props["connection"]),
				"url"=>$cmd["url"],
			]);
			throw new \RuntimeException("curl_exec() failed. errno=" . curl_errno($this->props["connection"]));
		}
		$this->logger->debug("ret={ret}",["method"=>__METHOD__, "ret"=>$ret]);

		// Convert response
		$this->props["lastResponse"] = $this->convertResponse($cmd, $ret);

		// Check response
		$this->checkResponse($cmd, $this->props["lastResponse"]);

		return $this->getResultCount($cmd, $this->props["lastResponse"]);

	}

	// -------------------------------------------------------------------------
	//	Protected
	// -------------------------------------------------------------------------

	/**
	 * Init curl.
	 *
	 * @param		object			$cmd				Database command object.
	 */
	protected function initCurl($cmd)
	{

		// Common
		curl_setopt($this->props["connection"], CURLOPT_URL, $cmd["url"]);
		curl_setopt($this->props["connection"], CURLOPT_CUSTOMREQUEST, $cmd["method"]);

		// Body
		if (($cmd["query"] ?? null) !== null)
		{
			curl_setopt($this->props["connection"], CURLOPT_POSTFIELDS, $cmd["query"]);
			$this->logger->info("query={query}", ["method"=>__METHOD__, "query"=>$cmd["query"]]);
		}

		// Custom
		foreach ((array)($this->options["curlOptions"] ?? null) as $key => $value)
		{
			$value = ( is_string($value) && substr($value, 0, 5) == "CURL_" ? constant($value) : $value);
			curl_setopt($this->props["connection"], constant($key), $value);
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Convert response.
	 *
	 * @param		object			$cmd				Database command object.
	 * @param		string			$response			Response.
	 *
	 * @return		object								Converted response.
	 */
	protected function convertResponse($cmd, string $response)
	{

		return $response;

	}

	// -------------------------------------------------------------------------

	/**
	 * Check response.
	 *
	 * @param		object			$cmd				Database command object.
	 * @param		object			$response			Response.
	 */
	protected function checkResponse($cmd, $response)
	{
	}

	// -------------------------------------------------------------------------

	/**
	 * Get query result count.
	 *
	 * @param		object			$cmd				Database command object.
	 * @param		string			$response			Response.
	 *
	 * @return		int									Record count.
	 */
	protected function getResultCount($cmd, string $response)
	{

		return 1;

	}

}
