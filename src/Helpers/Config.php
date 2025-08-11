<?php
namespace Yshabanei\BugTracker\Helpers;

use Yshabanei\BugTracker\exceptions\ConfigFileNotFoundException;

class Config
{
    /**
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
     * Get config content or a specific key from the config.
     * Assumes config files return an array.
     *
     * @throws ConfigFileNotFoundException
     */
    public static function get(string $filename, $key = null)
    {
        $config = include realpath(__DIR__ . "/../configs/" . $filename . ".php");

        if (!is_array($config)) {
            return null;
        }

        if (is_null($key)) {
            return $config;
        }

        return $config[$key] ?? null;
    }
}
