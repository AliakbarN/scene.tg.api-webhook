<?php

namespace SceneApi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserData extends Model
{
    protected $table = 'bot_user_data';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function fromArray(array $data): UserData
    {
        $userData = new self();

        $userData->data = json_encode($data);

        return $userData;
    }

    public function toArray(): array
    {
        return json_decode($this->data, true);
    }
}