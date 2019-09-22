<?php

ini_set('max_execution_time', 0);
ob_end_flush();
ob_start();

function Output(string $line)
{
    echo PHP_EOL . '<br>[' . date('H:i:s') . '] ' . $line;
    ob_flush();
}

function DownloadEngine(string $url)
{
    Output('Downloading...');
    ob_flush();
    try {
        file_put_contents('./EasyCMS.zip', fopen($url, 'r'));
        Output('Download has been completed, start unzip...');

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
                @rmdir('./' . $folderName);
                unlink('./EasyCMS.zip');
                if (is_file('./README.md')) {
                    unlink('./README.md');
                }
            } else {
                $zip->close();
                Output('Error unzip, archive is empty!');
            }
        } else {
            Output('Error unzip, not found class ZipArchive! Please unzip EasyCMS.zip arhive, after use <a href="/install.php">next step</a>!');
        }
    } catch (\Throwable $ex) {
        Output('Exception! <a href="/install.php?start_install">Maybe reset?</a>?<br>' . PHP_EOL . $ex);
    }
    Output('Finish! <a href="/install.php">Next step</a>?');

}

if (is_dir('./engine') == false) {
    if (isset($_GET['start_install'])) {
        DownloadEngine('https://github.com/iamryuzaki/EasyCMS/archive/master.zip');
    } else
        Output('EasyCMS not found, <a href="?start_install">start install</a>?');
}

try {
    require './engine/config.php';
    require './engine/bootstrap.php';

    if (Bootstrap::InitInstall()) {
        Bootstrap::DoLoadLibrary('Engine');
        $newKey = substr(md5(microtime()), 0, 8);
        Engine::ChangeAppKey($newKey);
    }

} catch (\Throwable $ex) {
    Output('Exception: ' . $ex);
}