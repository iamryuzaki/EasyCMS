<?php
require './engine/config.php';
require './engine/bootstrap.php';

if (Bootstrap::InitApp()) {
    $plugins = PluginManager::GetRepositoryPlugins('iamryuzaki/EasyCMS-Plugins2');
    header('Content-Type: text/plain');
    print_r($plugins);
    PluginManager::DownloadPlugin('DemoPlugin');
}