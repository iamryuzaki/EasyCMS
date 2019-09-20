<?php


class DemoPlugin
{
    public static function CanInitApp(bool &$result): bool
    {
        echo 'DemoPlugin::CanInitApp - has been override Bootstrap::InitApp and return false =)';
        $result = false;
        return true;
    }
}