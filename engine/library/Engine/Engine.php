<?php

class Engine
{
    public static function ChangeAppKey(string $newKey): bool
    {
        if (strlen($newKey) == 0) {
            echo 'You a new APP_KEY is empty' . PHP_EOL;
            return false;
        }
        $tree = scandir('./');
        foreach ($tree as $node) {
            if ($node != '.' and $node != '..' and is_dir('./' . $node)) {
                if (strlen($node) >= 3) {
                    $appSubstr = substr($node, 0, 3);
                    if ($appSubstr == 'app') {
                        rename('./' . $node, './app.' . $newKey);
                    }
                }
                if (strlen($node) >= 10) {
                    $appSubstr = substr($node, 0, 10);
                    if ($appSubstr == 'engine_app') {
                        rename('./' . $node, './engine_app.' . $newKey);
                    }
                }
            }
        }

        $GLOBALS['CONFIG']['APP_KEY'] = $newKey;
        self::RewriteConfig();
        return true;
    }

    public static function UninstallEngine(): bool
    {
        $tree = scandir('./');
        foreach ($tree as $node) {
            if ($node != '.' and $node != '..' and is_dir('./' . $node)) {
                if (strlen($node) >= 3) {
                    $appSubstr = substr($node, 0, 3);
                    if ($appSubstr == 'app') {
                        rename('./' . $node, './app');
                    }
                }
                if (strlen($node) >= 10) {
                    $appSubstr = substr($node, 0, 10);
                    if ($appSubstr == 'engine_app') {
                        rename('./' . $node, './engine_app');
                    }
                }
            }
        }

        $GLOBALS['CONFIG']['APP_KEY'] = 'secure';
        self::RewriteConfig();
        return true;
    }

    public static function RewriteConfig(): bool
    {
        $newConfig = '<?php ';
        $func = function (array $array, array $path, callable $func): string {
            $result = '';
            foreach ($array as $k => $v) {
                if (is_array($v) == false) {
                    $result .= PHP_EOL . '$GLOBALS';
                    foreach ($path as $item) {
                        $result .= '[\'' . $item . '\']';
                    }
                    $result .= '[\'' . $k . '\'] = \'' . $v . '\';';
                } else {
                    $newPath = $path;
                    $newPath[] = $k;
                    $result .= $func($v, $newPath, $func);
                }
            }
            return $result;
        };
        $newConfig .= $func($GLOBALS['CONFIG'], ['CONFIG'], $func);
        file_put_contents('./engine/config.php', $newConfig);
        return true;
    }
}