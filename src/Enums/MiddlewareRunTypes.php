<?php

namespace SceneApi\Enums;

enum MiddlewareRunTypes: string
{
    case ONCE = 'once';
    case MULTIPLE = 'multiple';
}