<?php

namespace SceneApi\Services\Helpers;

class GetScenes
{
    public static function getSceneClasses(string $dir, string $scenesNameSpace) : array
    {
        $result = [];
        $files = scandir($dir);

        foreach ($files as $file)
        {
            $sceneClassName = (explode('.', $file))[0];
            $result[] = str_replace('/', '\\', ($scenesNameSpace . '/' . $sceneClassName));
        }

        return $result;
    }
}
