<?php

namespace SceneApi\Middlewares;

use SceneApi\SceneManager;
use SceneApi\Services\BaseMiddleware;
use SergiX44\Nutgram\Nutgram;

class VerifyUser extends BaseMiddleware
{
    public function handle(Nutgram $bot, SceneManager $manager, callable $next): void
    {
        $bot->sendMessage('middleware');
        // 897807394
        if ($bot->userId() !== 897807394) {
            $bot->sendMessage('Access denied');

            $manager->changeUserState(false);

            return;
        } else {
            $bot->sendMessage('Go on');
        }

        $next();
    }
}