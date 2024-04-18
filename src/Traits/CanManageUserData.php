<?php

namespace SceneApi\Traits;

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
}