<?php

namespace SceneApi\Services\ODT;

class UserState
{
    public string $sceneName;
    public bool $isActive;
    public bool $isEnter;

    public function __construct(string $sceneName, bool $isActive, bool $isEnter)
    {
        $this->sceneName = $sceneName;
        $this->isActive = $isActive;
        $this->isEnter = $isEnter;
    }
}
