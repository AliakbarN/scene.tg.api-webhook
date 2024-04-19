<?php

namespace SceneApi\Services\Helpers;

class GetScenes
{
    public static function get(string $dir, string $namespace): array
    {
        $files = [];
        $res = [];

        self::scanDirectory($dir, $files);

        print_r($files);

        foreach ($files as $file)
        {
//            $partSceneClassName = str_replace('.php', '', $file);
//            $partSceneClassName = implode('/', array_slice(explode('/', $partSceneClassName), -1));
//            $res[] = str_replace('/', '\\', ($namespace . '/' . $partSceneClassName));
            $res[] = self::generateSceneClassName($dir, $file, $namespace);
        }

        return $res;
    }

    protected static function scanDirectory(string $directory, array &$files): void
    {
        $contents = scandir($directory);

        // Loop through the contents of the directory
        foreach ($contents as $item) {
            // Skip . and .. directories
            if ($item == '.' || $item == '..') {
                continue;
            }

            // Construct the full path
            $path = $directory . '/' . $item;

            // If the item is a directory, recursively scan it
            if (is_dir($path)) {
                self::scanDirectory($path, $files);
            } else {
                // If it's a file, add it to the $files array
                $files[] = $path;
            }
        }
    }

    protected static function generateSceneClassName(string $rootDir, string $filename, string $namespace): string
    {
        return str_replace('/', '\\', ($namespace . str_replace($rootDir, '', str_replace('.php', '', $filename))));
    }
}
