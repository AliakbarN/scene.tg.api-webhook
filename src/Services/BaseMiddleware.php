<?php

namespace SceneApi\Services;

use SergiX44\Nutgram\Nutgram;

abstract class BaseMiddleware
{
    /**
     * @param Nutgram $bot
     * @param callable $next
     * @return void
     */
    public function handle(Nutgram $bot, callable $next) :void {}
}
