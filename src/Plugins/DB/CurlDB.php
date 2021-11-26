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
			"dsn = {dsn}, user = {user}, password = {password}", ["method"=>__METHOD__, "dsn"=>$this->props["dsn"], "user"=>$this->props["user"], "password"=>substr($this->props["password"], 0, 1) . "*******"]
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

			$this->logger->debug("dsn = {dsn}", ["method"=>__METHOD__, "dsn"=>$this->props["dsn"]]);
		}

	}

	// -------------------------------------------------------------------------

	public function select($tableName, $fields, $keys = null, $orders = null, $limit = null, $offset = null)
	{

		list($query) = $this->buildQuerySelect($tableName, $fields, $keys, $orders, $limit, $offset);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "GET";
		$cmd["tableName"] = $tableName;
		$cmd["url"]  = $this->buildUrl($cmd);

		return $this->getData($cmd);

	}

	// -------------------------------------------------------------------------

	public function selectById($tableName, $fields, $id)
	{

		list($query) = $this->buildQuerySelect($tableName, $fields);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "GET";
		$cmd["tableName"] = $tableName;
		$cmd["id"] = $id["value"];
		$cmd["url"]  = $this->buildUrl($cmd);

		return $this->getData($cmd);

	}

    // -------------------------------------------------------------------------

	public function insert($tableName, $fields)
	{

		list($query) = $this->buildQueryInsert($tableName, $fields);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "POST";
		$cmd["tableName"] = $tableName;
		$cmd["url"]  = $this->buildUrl($cmd);

		return $this->execute($cmd);

	}

    // -------------------------------------------------------------------------

	public function insertWithId($tableName, $fields, $id)
	{

		list($query) = $this->buildQueryInsert($tableName, $fields);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "POST";
		$cmd["tableName"] = $tableName;
		$cmd["id"] = $id;
		$cmd["url"]  = $this->buildUrl($cmd);

		return $this->execute($cmd);

	}

	// -------------------------------------------------------------------------

	public function update($tableName, $fields, $keys = null)
	{

		list($query) = $this->buildQueryUpdate($tableName, $fields, $keys);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "POST";
		$cmd["tableName"] = $tableName;
		$cmd["url"]  = $this->buildUrl($cmd);

		return $this->execute($cmd);

	}

	// -------------------------------------------------------------------------

	public function updateById($tableName, $fields, $id)
	{

		list($query) = $this->buildQueryUpdateById($tableName, $fields, $id);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "PUT";
		$cmd["tableName"] = $tableName;
		$cmd["id"] = $id["value"];
		$cmd["url"]  = $this->buildUrl($cmd);

		return $this->execute($cmd);

	}

	// -------------------------------------------------------------------------

	public function delete($tableName, $keys)
	{

		list($query) = $this->buildQueryDelete($tableName, $keys);
		$cmd = $this->createCommand($query);
		$cmd["method"] = "POST";
		$cmd["tableName"] = $tableName;
		$cmd["url"]  = $this->buildUrl($cmd);

		return $this->execute($cmd);

	}

	// -------------------------------------------------------------------------

	public function deleteById($tableName, $id)
	{

		$cmd = array();
		$cmd["method"] = "DELETE";
		$cmd["tableName"] = $tableName;
		$cmd["id"] = $id["value"];
		$cmd["url"]  = $this->buildUrl($cmd);

		return $this->execute($cmd);

	}

    // -------------------------------------------------------------------------

	public function execute($cmd, $params = null)
	{

		$this->logger->info("method = {httpmethod}, url = {url}", ["method"=>__METHOD__, "httpmethod"=>$cmd["method"], "url"=>$cmd["url"]]);

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
		$this->logger->debug("ret = {ret}",["method"=>__METHOD__, "ret"=>$ret]);

		// Convert response
		$this->props["lastResponse"] = $this->convertResponse($cmd, $ret);

		// Check response
		$this->checkResponse($cmd, $this->props["lastResponse"]);

		return $this->getResultCount($cmd, $this->props["lastResponse"]);

	}

    // -------------------------------------------------------------------------

	public function createCommand($query = null)
	{

		$cmd = array();

		if ($query !== null)
		{
			$cmd["query"] = json_encode($query);
		}

		return $cmd;

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
			$this->logger->info("query = {query}", ["method"=>__METHOD__, "query"=>$cmd["query"]]);
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
	 * Build a url.
	 *
	 * @param		object			$cmd				Database command object.
	 *
	 * @return		string								Url.
	 */
	abstract protected function buildUrl($cmd);

	// -------------------------------------------------------------------------

	/**
	 * Convert response.
	 *
	 * @param		object			$cmd				Database command object.
	 * @param		object			$response			Response.
	 */
	protected function convertResponse($cmd, $response)
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
	 * @param		object			$response			Response.
	 */
	protected function getResultCount($cmd, $response)
	{

		return 1;

	}

}
