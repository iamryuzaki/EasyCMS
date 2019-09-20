<?php

if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    require './engine/config.php';
    require './engine/bootstrap.php';

    Bootstrap::DoLoadLibrary('Engine');
    if (Bootstrap::HasAppInstalled()) {
        Engine::UninstallEngine();
        echo 'Completed!';
    } else
        header('Location: /', false, 302);
} else
    header('Location: /install.php', false, 302);