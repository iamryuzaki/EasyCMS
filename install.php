<?php

require './engine/config.php';
require './engine/bootstrap.php';

if (Bootstrap::InitInstall()) {
    Bootstrap::DoLoadLibrary('Engine');
    Engine::ChangeAppKey(substr(md5(microtime()), 0, 8));
}
