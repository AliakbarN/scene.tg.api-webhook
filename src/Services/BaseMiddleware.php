<?php

namespace SceneApi\Services;

use SceneApi\SceneManager;
use SergiX44\Nutgram\Nutgram;

abstract class BaseMiddleware
{
    /**
     * @param Nutgram $bot
     * @param SceneManager $manager
     * @param callable $next
     * @return void
     */
    abstract public function handle(Nutgram $bot, SceneManager $manager, callable $next): void;
}
