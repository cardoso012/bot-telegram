<?php

namespace Televip\Config;

class Config{

  private function __construct(){}

  public static function getDatabaseConfig()
  {
    $config = parse_ini_file(__DIR__ . '/config.ini', true);
    return $config['database'];
  }
  
  public static function getCentralTelevipConfig()
  {
    $config = parse_ini_file(__DIR__ . '/config.ini', true);
    return $config['central_televip']['token'];
  }
  
  public static function getClientTelevipConfig()
  {
    $config = parse_ini_file(__DIR__ . '/config.ini', true);
    return $config['client_televip']['client_token'];
  }

}