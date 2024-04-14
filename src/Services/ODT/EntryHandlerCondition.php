<?php

namespace SceneApi\Services\ODT;

class EntryHandlerCondition
{
    public string $scene;
    public string $handler;
    public string $condition;

    public function __construct(string $scene, string $handler, string $condition)
    {
        $this->scene = $scene;
        $this->handler = $handler;
        $this->condition = $condition;
    }
}
