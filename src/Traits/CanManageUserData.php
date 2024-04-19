<?php

namespace SceneApi\Traits;

use SceneApi\Models\UserData;

trait CanManageUserData
{

    protected array $userData = [];

    public function setData(array $data) :void
    {
        foreach ($data as $key => $value)
        {
            $this->userData[$key] = $value;
        }
    }

    public function getData(string $key) :mixed
    {
        if (!array_key_exists($key, $this->userData)) {
            return null;
        }

        return $this->userData[$key];
    }

    protected function setUserData(UserData $userData): void
    {
        $this->userData = $userData->toArray();
    }

    protected function getUserDataForUpdate(): array
    {
        return [
            'data' => json_encode($this->userData),
        ];
    }
}