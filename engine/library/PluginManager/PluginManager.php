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
                            self::$LoadedPlugins[$k] = $v;
                        }
                    }
                } catch (\Exception $ex) {
                    Bootstrap::OnExceptionReceived($ex);
                }
                if (count(self::$LoadedPlugins) > 0) {
                    foreach (self::$LoadedPlugins as $k => $v) {
                        try {
                            require_once './app.' . $GLOBALS['CONFIG']['APP_KEY'] . '/plugins/' . $k . '/' . $k . '.php';
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
    }
}