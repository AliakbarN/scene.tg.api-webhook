<?php

namespace SceneApi\Services\Traits;

use Exception;
use SceneApi\Services\BaseMiddleware;
use SceneApi\Services\BaseScene;
use SergiX44\Nutgram\Nutgram;

trait MiddlewaresManager
{
    /**
     * @var BaseMiddleware[]
     */
    protected array $initiatedMiddlewares = [

    ];

    /**
     * @throws Exception
     */
    protected function checkMiddlewares(BaseScene $scene) :void
    {
        $middlewares = $scene->middlewares;

        foreach ($middlewares as $middleware)
        {
            if (!is_a($middleware, BaseMiddleware::class, true)) {
                $this->log('The class [' . $middleware .'] is not extended by BaseMiddleware');
            }
        }
    }

    /**
     * @return BaseMiddleware[]
     */
    protected function initiateMiddlewares(array $middlewares) :array
    {
        $result = [];
        foreach ($middlewares as $middleware)
        {
            if (!array_key_exists($middleware, $this->initiatedMiddlewares)) {
                $this->initiatedMiddlewares[$middleware] = new ($middleware);
            }

            $result[] = $this->initiatedMiddlewares[$middleware];
        }

        return $result;
    }

    /**
     * @param Nutgram $bot
     * @param BaseMiddleware[] $middlewares
     * @return bool|null
     */
    protected function manageMiddlewares(Nutgram $bot, array $middlewares) :bool|null
    {
        if (count($middlewares) === 0) {
            return null;
        }

        $bot->{"customData"} = [];
        $i = 0;
        $allData = [];
        $breakDownChain = false;

        while ((count($middlewares) - 1) === $i)
        {
            $x = $i;
            $middleware = $middlewares[$i];
            $middleware->handle($bot, function (array $data = null) use (&$i, &$allData)
            {
                $i++;

                if ($data !== null) {
                    foreach ($data as $key => $value)
                    {
                        $allData[$key] = $value;
                    }
                }
            });

            if ($i === $x) {
                $breakDownChain = true;
                break;
            }

            foreach ($allData as $key => $value)
            {
                $bot->customData[$key] = $value;
            }
        }

        $i = 0;
        $allData = [];

        usleep(500);

        return $breakDownChain;
    }
}
