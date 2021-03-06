<?php

namespace RoyBongers\CertbotDns01;

class Config
{
    private $config = [];

    public function __construct()
    {
        if (file_exists(APP_ROOT . '/config/config.php')) {
            $this->config = include(APP_ROOT . '/config/config.php');
        } elseif (file_exists(APP_ROOT . '/config/transip.php')) {
            $this->config = include(APP_ROOT . '/config/transip.php');
        }
    }

    /**
     * Fetch a value from the config file or ENV variable. ENV variables are always
     * an uppercase variant from the config file keys which are lowercase.
     *
     * @param  string  $key The config key to search for, should always be lowercase.
     * @param  null  $default Optional default value in case the config is not found.
     * @return string|null
     */
    public function get(string $key, $default = null): ?string
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        $envValue = getenv(strtoupper($key));
        if ($envValue !== false) {
            return $envValue;
        }

        return $default;
    }
}
