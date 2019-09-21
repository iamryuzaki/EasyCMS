<?php
require './engine/config.php';
require './engine/bootstrap.php';

if (Bootstrap::InitApp()) {
    $plugins = PluginManager::GetRepositoryPlugins('iamryuzaki/EasyCMS-Plugins');
    header('Content-Type: text/plain');
    foreach ($plugins as $k => $v) {
        PluginManager::DownloadPlugin($v, $k);
    }
    print_r($plugins);
}