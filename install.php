<?php

if (is_dir('./engine') == false) {
    if (isset($_GET['start_install'])) {
        ini_set('max_execution_time', 0);
        header('Content-type: text/html; charset=utf-8');
        header('Surrogate-Control: BigPipe/1.0');
        header("Cache-Control: no-cache, must-revalidate");
        header('X-Accel-Buffering: no');
        echo '[' . date('H:i:s') . '] Downloading...';
        flush();
        $func = function (string $repoUrl, string $path, callable $func) {
            $apiUrl = 'https://api.github.com/repos/' . $repoUrl . '/contents' . $path;
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => 'Cookie: logged_in=yes; dotcom_user=iamryuzaki' . "\r\n" .
                            'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132'
                    ]
                ]);

                $content = @file_get_contents($apiUrl, false, $context);
                if ($content) {
                    $json = json_decode($content, true);
                    foreach ($json as $item) {
                        if ($item['type'] == 'file') {
                            $file_content = @file_get_contents($item['download_url']);
                            if ($file_content) {
                                file_put_contents('.' . $path . '/' . $item['name'], $file_content);
                                echo PHP_EOL . '<br>[' . date('H:i:s') . '] File: ' . '.' . $path . '/' . $item['name'] . ', size: ' . $item['size'] . 'byte - <font color="gren">Loaded...</font>';
                                flush();
                            }
                        } else {
                            if (is_dir('.' . $path . '/' . $item['name']) == false) {
                                mkdir('.' . $path . '/' . $item['name']);
                                echo PHP_EOL . '<br>[' . date('H:i:s') . '] Directory: ' . '.' . $path . '/' . $item['name'] . '/ - <font color="gren">Created...</font>';
                                flush();
                            }
                            $func($repoUrl, $path . ($path != '/' ? '/' : '') . $item['name'], $func);
                        }
                    }
                }
            } catch (\Throwable $ex) {
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
