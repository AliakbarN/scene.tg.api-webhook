<?php

namespace SceneApi\Services\ODT;

use SceneApi\Models\User;

class UserState
{
    public string $sceneName;
    public bool $isActive;
    public bool $isEnter;
    public int $telegramId;

    public function __construct(string $sceneName, int $telegramId, bool $isActive, bool $isEnter)
    {
        $this->sceneName = $sceneName;
        $this->isActive = $isActive;
        $this->isEnter = $isEnter;
        $this->telegramId = $telegramId;
    }

    public static function fromModel(User $user): UserState
    {
        return new self($user->scene, $user->tg_id, $user->is_active, $user->is_enter);
    }
}
