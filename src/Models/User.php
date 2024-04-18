<?php

namespace SceneApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use SceneApi\Services\ODT\UserState;

class User extends Model
{

    protected $table = 'bot_users';

    const ID = 'tg_id';

    public function userData(): HasOne
    {
        return $this->hasOne(UserData::class, 'bot_user_id');
    }

    public static function fromDTO(UserState $userState): User
    {
        $user = new self();

        $user->scene = $userState->sceneName;
        $user->tg_id = $userState->telegramId;
        $user->is_active = $userState->isActive;
        $user->is_enter = $userState->isEnter;

        return $user;
    }
}