<?php

class Bootstrap
{
    private static $LoadedLibrary = [];

    private static function InitPluginManager()
    {
        self::DoLoadLibrary('PluginManager');
        PluginManager::Init();
        PluginManager::CallHook('OnInitPluginManagerFinish');
    }

    public static function InitApp(): bool
    {
        self::InitPluginManager();
        $result = true;
        $hasOverride = PluginManager::CallHook('CanInitApp', [&$result]);
        if ($hasOverride == false) {
            if (self::HasAppInstalled() == false) {
                http_response_code(500);
                echo 'Please, use <a href="/install.php">install.php</a>' . PHP_EOL;
                return false;
            }
        }
        PluginManager::CallHook('OnInitAppFinish', [&$result]);
        return $result;
    }

    public static function InitInstall(): bool
    {
        self::InitPluginManager();
        $result = true;
        $hasOverride = PluginManager::CallHook('CanInitInstall', [&$result]);
        if ($hasOverride == false) {
            if (self::HasAppInstalled() == true) {
                http_response_code(500);
                echo 'Application has been installed, not use install.php please! ' . PHP_EOL;
                if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
                    echo 'Or use <a href="/uninstall.php">uninstall.php</a>';
                }
                return false;
            }
        }
        PluginManager::CallHook('OnInitInstallFinish', [&$result]);
        return $result;
    }

    public static function HasAppInstalled(): bool
    {
        return is_dir('./app.' . $GLOBALS['CONFIG']['APP_KEY']) == true;
    }

    public static function HasLoadedLibrary(string $name): bool
    {
        return isset(self::$LoadedLibrary[$name]) == true;
    }

    public static function DoLoadLibrary(string $name): bool
    {
        $result = false;
        $hasOverride = PluginManager::CallHook('CanDoLoadLibrary', [&$result]);
        if ($hasOverride == false) {
            if (self::HasLoadedLibrary($name) == false) {
                try {
                    require_once __DIR__ . '/library/' . $name . '/' . $name . '.php';
                    self::$LoadedLibrary[$name] = true;
                    return true;
                } catch (\Exception $ex) {
                    self::OnExceptionReceived($ex);
                }
            }
        }
        PluginManager::CallHook('OnDoLoadLibraryFinish', [&$result]);
        return $result;
    }

    public static function OnExceptionReceived(\Exception $ex)
    {
        PluginManager::CallHook('OnExceptionReceived', [$ex]);
    }
}