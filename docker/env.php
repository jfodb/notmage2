<?php
return array (
  'backend' =>
  array (
    'frontName' => getenv('ADMIN_URI'),
  ),
  'crypt' =>
  array (
    'key' => getenv('CRYPT_KEY'),
  ),
	'session' =>
	array (
	  'save' => 'redis',
	  'redis' =>
	  array (
	    'host' => getenv('REDIS_HOST'),
	    'port' => getenv('REDIS_PORT'),
	    'password' => '',
	    'timeout' => '2.5',
	    'persistent_identifier' => '',
	    'database' => '2',
	    'compression_threshold' => '2048',
	    'compression_library' => 'gzip',
	    'log_level' => '1',
	    'max_concurrency' => '6',
	    'break_after_frontend' => '5',
	    'break_after_adminhtml' => '30',
	    'first_lifetime' => '600',
	    'bot_first_lifetime' => '60',
	    'bot_lifetime' => '7200',
	    'disable_locking' => '0',
	    'min_lifetime' => '60',
	    'max_lifetime' => '2592000'
	  )
	),
  'db' =>
  array (
    'table_prefix' => '',
    'connection' =>
    array (
      'default' =>
      array (
        'host' => getenv('MYSQL_HOST'),
        'dbname' => getenv('MYSQL_DATABASE'),
        'username' => getenv('MYSQL_USER'),
        'password' => getenv('MYSQL_PASSWORD'),
        'model' => 'mysql4',
        'engine' => 'innodb',
        'initStatements' => 'SET NAMES utf8;',
        'active' => '1',
      ),
    ),
  ),
  'cache' =>
  array (
    'frontend' =>
    array (
      'default' =>
      array (
        'backend' => 'Cm_Cache_Backend_Redis',
        'backend_options' =>
        array (
          'server' => getenv('REDIS_HOST'),
          'database' => '0',
          'port' => getenv('REDIS_PORT'),
        ),
      ),
      'page_cache' =>
      array (
        'backend' => 'Cm_Cache_Backend_Redis',
        'backend_options' =>
        array (
          'server' => getenv('REDIS_HOST'),
          'port' => getenv('REDIS_PORT'),
          'database' => '1',
          'compress_data' => '0',
        ),
      ),
    ),
  ),
  'resource' =>
  array (
    'default_setup' =>
    array (
      'connection' => 'default',
    ),
  ),
  'x-frame-options' => 'SAMEORIGIN',
  'MAGE_MODE' => getenv('MAGE_MODE'),
  'cache_types' =>
  array (
    'config' => 1,
    'layout' => 1,
    'block_html' => 1,
    'collections' => 1,
    'reflection' => 1,
    'db_ddl' => 1,
    'eav' => 1,
    'customer_notification' => 1,
    'full_page' => 1,
    'config_integration' => 1,
    'config_integration_api' => 1,
    'translate' => 1,
    'config_webservice' => 1,
  ),
  'install' =>
  array (
    'date' => 'Tue, 08 Aug 2017 16:23:37 +0000',
  ),
);
