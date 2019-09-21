<?php

class DemoPlugin
{
    public static function CanInitApp(bool &$result): bool
    {
        if (isset($_GET['stop'])) {
            echo 'DemoPlugin::CanInitApp - has been override Bootstrap::InitApp and return false =)';
            $result = false;
            return true;
        }
        return false;
    }
}
