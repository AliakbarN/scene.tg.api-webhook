<?php

namespace SceneApi\Traits;

use Exception;
use SceneApi\Services\BaseMiddleware;
use SceneApi\Services\BaseScene;
use SergiX44\Nutgram\Nutgram;

trait MiddlewaresManager
{
    /**
     * @var BaseMiddleware[]
     */
    protected array $initiatedMiddlewares = [];

    /**
     * @throws Exception
     */
    protected function checkMiddlewares(BaseScene $scene) : void
    {
        foreach ($scene->middlewares as $middleware) {
            if (!is_a($middleware, BaseMiddleware::class, true)) {
                $this->logger->error('The class [' . $middleware . '] is not extended by BaseMiddleware');
            }
        }
    }

    /**
     * Initialize middlewares and cache them.
     *
     * @return BaseMiddleware[]
     */
    protected function initiateMiddlewares(array $middlewares): array
    {
        $result = [];

        foreach ($middlewares as $middleware) {
            if (!array_key_exists($middleware, $this->initiatedMiddlewares)) {
                $this->initiatedMiddlewares[$middleware] = new $middleware;
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
    protected function manageMiddlewares(Nutgram $bot, array $middlewares): ?bool
    {
        if (empty($middlewares)) {
            return null;
        }

        $bot->customData = [];
        $i = 0;
        $allData = [];
        $breakDownChain = false;

        while ($i < count($middlewares)) {
            $currentMiddleware = $middlewares[$i];
            $x = $i;

            // Handle the middleware and pass a callback for further processing
            $currentMiddleware->handle($bot, $this, function (array $data = null) use (&$i, &$allData) {
                $i++;
                if ($data !== null) {
                    $allData = array_merge($allData, $data);
                }
            });

            // If the chain has been broken, stop the loop
            if ($i === $x) {
                $breakDownChain = true;
                break;
            }
        }

        // Merge the collected data into the bot's custom data
        $bot->customData = array_merge($bot->customData, $allData);

        return $breakDownChain;
    }
}

