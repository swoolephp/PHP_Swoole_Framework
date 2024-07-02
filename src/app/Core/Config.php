<?php
namespace App\Core;

class Config {
    private static $instance = null;
    private $settings = [];

    private function __construct() {
        $this->settings = require BASE_PATH . '/config/config.php';
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get($key = '') {
        if ($key && isset($this->settings[$key])){
            return $this->settings[$key];
        }
        return $this->settings ?? null;
    }
}
