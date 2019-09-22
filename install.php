<?php

if (is_dir('./engine') == false) {
    if (isset($_GET['start_install'])) {
        ini_set('max_execution_time', 0);
        ini_set('zlib.output_compression', 0);
        ini_set('implicit_flush', 1);
        header('Content-type: text/html; charset=utf-8');
        ob_end_flush();
        ob_start();
        echo '[' . date('H:i:s') . '] Downloading...';
        ob_flush();

        try {
            file_put_contents('./EasyCMS.zip', fopen('https://github.com/iamryuzaki/EasyCMS/archive/master.zip', 'r'));
            echo PHP_EOL . '<br>[' . date('H:i:s') . '] Download has been completed, start unzip...';

            if (class_exists('ZipArchive')) {
                $folderName = '';
                $zip = new ZipArchive;
                $zip->open('./EasyCMS.zip');
                if ($zip->numFiles > 0) {
                    $folderName = basename($zip->getNameIndex(0));
                    $zip->extractTo('./');
                    $zip->close();
                    echo PHP_EOL . '<br>[' . date('H:i:s') . '] Unzip has been completed, start move...';
                    $files = scandir('./' . $folderName);
                    foreach ($files as $item) {
                        if ($item != '.' && $item != '..') {
                            rename('./' . $folderName . '/' . $item, './' . $item);
                        }
                    }
                    @unlink('./' . $folderName);
                } else {
                    $zip->close();
                    echo PHP_EOL . '<br>[' . date('H:i:s') . '] Error unzip, archive is empty!';
                }
            } else
            {
                echo PHP_EOL . '<br>[' . date('H:i:s') . '] Error unzip, not found class ZipArchive! Please unzip EasyCMS.zip arhive, after use <a href="/install.php">next step</a>!';
            }
        } catch (\Throwable $ex) {
            die(PHP_EOL . '<br>[' . date('H:i:s') . '] Exception! <a href="/install.php?start_install">Maybe reset?</a>?<br>' . PHP_EOL . $ex);
        }
        die(PHP_EOL . '<br>[' . date('H:i:s') . '] Finish! <a href="/install.php">Next step</a>?');
    } else
        die('EasyCMS not found, <a href="?start_install">start install</a>?');
}

require './engine/config.php';
require './engine/bootstrap.php';

if (Bootstrap::InitInstall()) {
    Bootstrap::DoLoadLibrary('Engine');
    Engine::ChangeAppKey(substr(md5(microtime()), 0, 8));
}
