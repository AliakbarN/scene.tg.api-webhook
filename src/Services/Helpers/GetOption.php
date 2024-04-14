<?php

namespace SceneApi\Services\Helpers;

class GetOption
{
    protected array|null $options;

    public function __construct(array|null $options)
    {
        $this->options = $options;
    }

    public function get(string $key) :mixed
    {
        if ($this->options === null) {
            return false;
        }

        if (!array_key_exists($key, $this->options)) {
            return false;
        }

        return $this->options[$key];
    }
}
