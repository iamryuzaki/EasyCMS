<?php

class PluginManager
{
    private static array $LoadedPlugins = [];

    public static function Init(): bool
    {
        $path = './app/configs/plugins.' . APP_KEY . '.json';
        if (is_file($path) == true) {
            try {
                $content = file_get_contents($path);
                self::$LoadedPlugins = json_decode($content, true);
            } catch (\Exception $ex) {
                Bootstrap::OnExceptionReceived($ex);
            }
            if (count(self::$LoadedPlugins) > 0) {
                foreach (self::$LoadedPlugins as $k => $v) {
                    try {
                        require_once './app/plugins/' . $k . '.' . APP_KEY . '/' . $k . '.php';
                    } catch (\Exception $ex) {
                        Bootstrap::OnExceptionReceived($ex);
                    }
                }
                return true;
            }
        }
        return false;
    }

    public static function CallHookInPlugin(string $pluginName, string $methodName, array $params = []): bool
    {
        $result = false;
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
        return $result;
    }

    public static function CallHook(string $hookName, array $params = []): bool
    {
        $result = false;
        foreach (self::$LoadedPlugins as $k => $v) {
            $one = self::CallHookInPlugin($k, $hookName, $params);
            if ($one == true) {
                $result = true;
            }
        }
        return $result;
    }

    public static function GetRepositoryPlugins(string $repoUrl): array
    {

    }
}