<?php

class PluginManager
{
    private static $LoadedPlugins = [];
    private static $HasInited = false;

    public static function Init(): bool
    {
        if (self::$HasInited == false) {
            $path = './app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/configs/plugins.json';
            if (is_file($path) == true) {
                try {
                    $content = file_get_contents($path);
                    $json = json_decode($content, true);
                    foreach ($json as $k => $v) {
                        if ($v['status'] == true) {
                            if (is_dir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $k)) {
                                self::$LoadedPlugins[$k] = $v;
                            } else {
                                unset($json[$k]);
                                file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT));
                            }
                        }
                    }
                } catch (\Exception $ex) {
                    Bootstrap::OnExceptionReceived($ex);
                }
                if (count(self::$LoadedPlugins) > 0) {
                    foreach (self::$LoadedPlugins as $k => $v) {
                        try {
                            include_once './app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $k . '/' . $k . '.php';
                        } catch (\Exception $ex) {
                            Bootstrap::OnExceptionReceived($ex);
                        }
                    }
                    self::$HasInited = true;
                    return true;
                }
            }
        }
        return false;
    }

    public static function CallHookInPlugin(string $pluginName, string $methodName, array $params = []): bool
    {
        $result = false;
        if (self::$HasInited == true) {
            if (method_exists($pluginName, $methodName)) {
                try {
                    $result = call_user_func_array([$pluginName, $methodName], $params);
                    if (is_bool($result) == false) {
                        $result = true;
                    }
                } catch (\Exception $ex) {
                    Bootstrap::OnExceptionReceived($ex);
                }
            }
        }
        return $result;
    }

    public static function CallHook(string $hookName, array $params = []): bool
    {
        $result = false;
        if (self::$HasInited == true) {
            foreach (self::$LoadedPlugins as $k => $v) {
                $one = self::CallHookInPlugin($k, $hookName, $params);
                if ($one == true) {
                    $result = true;
                }
            }
        }
        return $result;
    }

    public static function GetRepositoryPlugins(string $repoUrl): array
    {
        $apiUrl = 'https://api.github.com/repos/' . $repoUrl . '/contents/';
        $result = [];
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'Cookie: logged_in=yes; dotcom_user=iamryuzaki' . "\r\n" .
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 YaBrowser/19.9.2.228 Yowser/2.5 Safari/537.36'
                ]
            ]);

            $content = file_get_contents($apiUrl, false, $context);
            $json = json_decode($content, true);
            foreach ($json as $item) {
                if ($item['type'] == 'dir') {
                    $result[$item['name']] = $repoUrl;
                }
            }
        } catch (\Exception $ex) {
            Bootstrap::OnExceptionReceived($ex);
        }
        return $result;
    }

    public static function DownloadPlugin(string $repoUrl, string $pluginName)
    {
        $func = function (string $repoUrl, string $path, callable $func) {
            $apiUrl = 'https://api.github.com/repos/' . $repoUrl . '/contents/' . $path;
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => 'Cookie: logged_in=yes; dotcom_user=iamryuzaki' . "\r\n" .
                            'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 YaBrowser/19.9.2.228 Yowser/2.5 Safari/537.36'
                    ]
                ]);

                $content = file_get_contents($apiUrl, false, $context);
                $json = json_decode($content, true);
                foreach ($json as $item) {
                    if ($item['type'] == 'file') {
                        $file_content = file_get_contents($item['download_url']);
                        file_put_contents('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $path . '/' . $item['name'], $file_content);
                    } else {
                        if (is_dir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $path . '/' . $item['name']) == false) {
                            mkdir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $path . '/' . $item['name']);
                        }
                        $func($repoUrl, $path . '/' . $item['name'], $func);
                    }
                }
            } catch (\Exception $ex) {
            }
        };
        if (is_dir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName) == false) {
            mkdir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName);
        }
        $func($repoUrl, $pluginName, $func);
    }
}