<?php
require './engine/config.php';
require './engine/bootstrap.php';

if (Bootstrap::InitApp()) {
    Bootstrap::DoLoadLibrary('RouteManager');
    RouteManager::Init();

}