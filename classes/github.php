<?php

namespace block_configurable_reports;

/**
 * Class github
 *
 * @package  block_configurablereports
 * @author   Juan leyva <http://www.twitter.com/jleyvadelgado>
 * @date     2009
 */
class github extends \curl {

    /**
     * @var string
     */
    protected string $repo = '';

    /**
     * Set repository
     *
     * @param string $repo
     * @return void
     */
    public function set_repo($repo) {
        $this->repo = $repo;
    }

    /**
     * Set a basic auth header.
     *
     * @param string $username The username to use.
     * @param string $password The password to use.
     */
    public function set_basic_auth(string $username, string $password): bool {
        $value = 'Basic ' . base64_encode($username . ':' . $password);
        $this->setHeader('Authorization:' . $value);

        return true;
    }

    /**
     * Get
     *
     * @param string $endpoint
     * @param array $params
     * @param array $options
     * @return bool|string
     */
    public function get(string $endpoint, array $params = [], array $options = []) {
        $repolink = 'https://api.github.com/repos/';
        $repolink .= $this->repo;
        $repolink .= $endpoint;

        return parent::get($repolink, $params, $options);
    }

}