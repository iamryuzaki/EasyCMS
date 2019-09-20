<?php

class Bootstrap
{
    private static $LoadedLibrary = [];

    public static function HasLoadedLibrary(string $name): bool
    {
        return isset(self::$LoadedLibrary[$name]);
    }

    public static function DoLoadLibrary(string $name): bool
    {
        if (self::HasLoadedLibrary($name) == false) {
            try {
                require_once __DIR__ . '/library/' . $name . '/' . $name . '.php';
                self::$LoadedLibrary[$name] = true;
                return true;
            } catch (\Exception $ex) {
                self::OnExceptionReceived($ex);
            }
        }
        return false;
    }

    public static function OnExceptionReceived(\Exception $ex) {

    }
}