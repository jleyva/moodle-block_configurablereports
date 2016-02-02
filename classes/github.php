<?php
namespace block_configurable_reports;

class github extends \curl {
	protected $repo = '';

	public function set_repo($repo) {
		$this->repo = $repo;
	}

	/**
	 * Set a basic auth header.
	 *
	 * @param string $username The username to use.
	 * @param string $password The password to use.
	 */
	public function set_basic_auth($username, $password) {
		$value = 'Basic '.base64_encode($username.':'.$password);
		$this->setHeader('Authorization:'. $value);
		return true;
	}

	public function get($endpoint, $params = array(), $options = array()) {
		$url = 'https://api.github.com/repos/';
		$url .= $this->repo;
		$url .= $endpoint;
		$result = parent::get($url, $params, $options);
		return $result;
	}
}