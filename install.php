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
        $func = function (string $repoUrl, string $path, callable $func) {
            $apiUrl = 'https://api.github.com/repos/' . $repoUrl . '/contents' . $path;
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => 'User-Agent: iamryuzaki/EasyCMS' . "\r\n" .
                            'Authorization: token 23af3da8dd7a35eac8bfce9e7ce68d28fc64be07'
                    ]
                ]);

                $content = @file_get_contents($apiUrl, false, $context);
                if ($content) {
                    $json = json_decode($content, true);
                    foreach ($json as $item) {
                        if ($item['type'] == 'file') {
                            $file_content = @file_get_contents($item['download_url']);
                            if ($file_content) {
                                file_put_contents('.' . $path . ($path != '/' ? '/' : '') . $item['name'], $file_content);
                                echo PHP_EOL . '<br>[' . date('H:i:s') . '] File: ' . '.' . $path . ($path != '/' ? '/' : '') . $item['name'] . ', size: ' . $item['size'] . 'byte - <font color="gren"><b>Loaded...</b></font>';
                                ob_flush();
                            }
                        } else {
                            if (is_dir('.' . $path . '/' . $item['name']) == false) {
                                mkdir('.' . $path . '/' . $item['name']);
                                echo PHP_EOL . '<br>[' . date('H:i:s') . '] Directory: ' . '.' . $path . '/' . $item['name'] . '/ - <font color="gren"><b>Created...</b></font>';
                                ob_flush();
                            }
                            $func($repoUrl, $path . ($path != '/' ? '/' : '') . $item['name'], $func);
                        }
                    }
                } else {
                    echo PHP_EOL . '<br>[' . date('H:i:s') . '] GET ' . $apiUrl . ' - <font color="red"><b>Failed...</b></font>';
                }
            } catch (\Throwable $ex) {
                echo PHP_EOL . '<br>[' . date('H:i:s') . '] GET ' . $apiUrl . ' - <font color="red"><b>Exception:</b></font> ' . $ex;
            }
        };
        $func('iamryuzaki/EasyCMS', '/', $func);
        die(PHP_EOL . '<br>[' . date('H:i:s') . '] Downloading has been completed! <a href="/install.php">Next step</a>?');
    } else
        die('EasyCMS not found, <a href="?start_install">start install</a>?');
}

require './engine/config.php';
require './engine/bootstrap.php';

if (Bootstrap::InitInstall()) {
    Bootstrap::DoLoadLibrary('Engine');
    Engine::ChangeAppKey(substr(md5(microtime()), 0, 8));
}
