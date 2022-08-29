<?php

$config = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';
$config['directory_list'][] = 'libs/';
$config['suppress_issue_types'][] = 'PhanPluginNeverReturnMethod';
$config['suppress_issue_types'][] = 'PhanInfiniteLoop';

return $config;
