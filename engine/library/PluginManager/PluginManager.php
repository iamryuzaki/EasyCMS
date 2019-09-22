<?php

class PluginManager
{
    private static $LoadedPlugins = [];
    private static $HasInited = false;

    public static function Init(): bool
    {
        $result = false;
        if (self::$HasInited == false) {
            $path = './app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/configs/plugins.json';
            if (is_file($path) == true) {
                try {
                    $content = @file_get_contents($path);
                    if ($content) {
                        $json = json_decode($content, true);
                        foreach ($json as $k => $v) {
                            if ($v['status'] == true) {
                                if (is_dir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $k)) {
                                    self::$LoadedPlugins[$k] = $v;
                                } else {
                                    unset($json[$k]);
                                    file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                                }
                            }
                        }
                    }
                } catch (\Throwable $ex) {
                    Bootstrap::OnExceptionReceived($ex);
                }
                if (count(self::$LoadedPlugins) > 0) {
                    foreach (self::$LoadedPlugins as $k => $v) {
                        try {
                            include_once './app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $k . '/' . $k . '.php';
                        } catch (\Throwable $ex) {
                            Bootstrap::OnExceptionReceived($ex);
                        }
                    }
                    self::$HasInited = true;
                    $result = true;
                }
            }
        }
        PluginManager::CallHook('OnPluginManagerInitFinish', [&self::$LoadedPlugins, &$result]);
        return $result;
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
                } catch (\Throwable $ex) {
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
        $hasOverride = PluginManager::CallHook('CanGetRepositoryPlugins', [&$repoUrl]);
        if ($hasOverride == false) {
            $apiUrl = 'https://api.github.com/repos/' . $repoUrl . '/contents/';
            $result = [];
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'header' => 'User-Agent: iamryuzaki/EasyCMS'."\r\n".
                            'Authorization: token 23af3da8dd7a35eac8bfce9e7ce68d28fc64be07'
                    ]
                ]);

                $content = @file_get_contents($apiUrl, false, $context);
                if ($content) {
                    $json = json_decode($content, true);
                    foreach ($json as $item) {
                        if ($item['type'] == 'dir') {
                            $result[$item['name']] = $repoUrl;
                        }
                    }
                }
            } catch (\Throwable $ex) {
                Bootstrap::OnExceptionReceived($ex);
            }
            return $result;
        }
        PluginManager::CallHook('OnGetRepositoryPluginsFinish', [&$repoUrl]);
    }

    public static function DownloadPlugin(string $repoUrl, string $pluginName = '')
    {
        if (strlen($pluginName) == 0) {
            $pluginName = $repoUrl;
            $repoUrl = 'iamryuzaki/EasyCMS-Plugins';
        }
        $hasOverride = PluginManager::CallHook('CanDownloadPlugin', [&$repoUrl, &$pluginName]);
        if ($hasOverride == false) {
            $func = function (string $repoUrl, string $path, callable $func) {
                $apiUrl = 'https://api.github.com/repos/' . $repoUrl . '/contents/' . $path;
                try {
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => 'User-Agent: iamryuzaki/EasyCMS'."\r\n".
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
                                    file_put_contents('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $path . '/' . $item['name'], $file_content);
                                }
                            } else {
                                if (is_dir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $path . '/' . $item['name']) == false) {
                                    mkdir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $path . '/' . $item['name']);
                                }
                                $func($repoUrl, $path . '/' . $item['name'], $func);
                            }
                        }
                    }
                } catch (\Throwable $ex) {
                }
            };
            if (is_dir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName) == false) {
                mkdir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName);
            }
            $func($repoUrl, $pluginName, $func);
            if (is_file('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName . '/' . $pluginName . '.php')) {
                $hasUpdate = false;
                try {
                    $path = './app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/configs/plugins.json';
                    $content = @file_get_contents($path);
                    if ($content) {
                        $json = json_decode($content, true);
                        $newStatusPlugin = false;
                        if (isset($json[$pluginName]) == true) {
                            $hasUpdate = true;
                            $newStatusPlugin = $json[$pluginName]['status'];
                        }

                        if (is_file('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName . '/' . $pluginName . '.json')) {
                            $content_plugin = file_get_contents('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName . '/' . $pluginName . '.json');
                            $json_plugin = @json_decode($content_plugin, true) ?? [];
                            $json[$pluginName] = $json_plugin;
                            $json[$pluginName]['status'] = $newStatusPlugin;
                        } else
                            $json[$pluginName] = ['status' => $newStatusPlugin];
                        file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                        $hasNoLoaded = false;
                        if (isset(self::$LoadedPlugins[$pluginName]) == false) {
                            $hasNoLoaded = true;
                            include_once './app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName . '/' . $pluginName . '.php';
                            self::$LoadedPlugins[$pluginName] = $json[$pluginName];
                        }
                        self::CallHookInPlugin($pluginName, 'OnThisPlugin' . ($hasUpdate == true ? 'Updated' : 'Installed'));
                        if ($hasNoLoaded == true) {
                            unset(self::$LoadedPlugins[$pluginName]);
                        }
                    }
                } catch (\Throwable $ex) {
                    Bootstrap::OnExceptionReceived($ex);
                }
            } else
                self::RemovePlugin($pluginName);
        }
        PluginManager::CallHook('OnDownloadPluginFinish', [&$repoUrl, &$pluginName]);
    }

    public static function RemovePlugin(string $pluginName)
    {
        $hasOverride = PluginManager::CallHook('CanRemovePlugin', [&$pluginName]);
        if ($hasOverride == false) {
            try {
                $path = './app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/configs/plugins.json';
                $content = @file_get_contents($path);
                if ($content) {
                    $json = json_decode($content, true);
                    if (isset($json[$pluginName])) {
                        unset($json[$pluginName]);
                        file_put_contents($path, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    }
                }
            } catch (\Throwable $ex) {
                Bootstrap::OnExceptionReceived($ex);
            }
            try {
                if (is_dir('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName)) {
                    $func = function (string $dir, callable $func) {
                        $files = array_diff(scandir($dir), array('.', '..'));
                        foreach ($files as $file) {
                            (is_dir($dir . '/' . $file)) ? $func($dir . '/' . $file, $func) : @unlink($dir . '/' . $file);
                        }
                        return @rmdir($dir);
                    };
                    $func('./app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $pluginName, $func);
                }
            } catch (\Throwable $ex) {
                Bootstrap::OnExceptionReceived($ex);
            }
            if (isset(self::$LoadedPlugins[$pluginName])) {
                self::CallHookInPlugin($pluginName, 'OnThisPluginRemoved');
                unset(self::$LoadedPlugins[$pluginName]);
            }
        }
        PluginManager::CallHook('OnRemovePluginFinish', [&$pluginName]);
    }
}