<?php
namespace Yshabanei\BugTracker\Helpers;

use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;

class Config
{
    /**
     * فایل config را به صورت رشته (متن) برمی‌گرداند
     *
     * @throws ConfigFileNotFoundException
     */
    public static function getFileContents(string $filename): string
    {
        $filePath = realpath(__DIR__ . "/../configs/" . $filename . ".php");

        if (!$filePath) {
            throw new ConfigFileNotFoundException("Config file '$filename.php' not found.");
        }

        return file_get_contents($filePath);
    }

    /**
     * محتویات فایل config را بارگذاری و به صورت آرایه بازمی‌گرداند
     * در صورت تعیین کلید، مقدار آن کلید را بازمی‌گرداند
     *
     * @param string $filename
     * @param null $key
     * @return array|null
     * @throws ConfigFileNotFoundException
     */
    public static function get(string $filename, $key = null): ?array
    {
        $filePath = realpath(__DIR__ . "/../configs/" . $filename . ".php");

        if (!$filePath) {
            throw new ConfigFileNotFoundException("Config file '$filename.php' not found.");
        }

        $config = include $filePath;

        if (!is_array($config)) {
            return null;
        }

        if (is_null($key)) {
            return $config;
        }

        return $config[$key] ?? null;
    }
}