<?php

class RouteManager
{
    private static $LoadedRoutes = [];

    public static function Init(): bool
    {

        PluginManager::CallHook('OnRouteManagerInitFinish', [&self::$LoadedRoutes]);
        return true;
    }
}