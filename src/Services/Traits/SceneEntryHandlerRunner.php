<?php

namespace SceneApi\Services\Traits;

use Exception;
use SergiX44\Nutgram\Nutgram;

trait SceneEntryHandlerRunner
{
    protected array $availableEnterConditions = [
        'command',
        'text',
        'callBackQuery'
    ];

    protected function enter(Nutgram $bot) :void
    {
        $this->manager->addUser($bot->userId(), $this->name, false);

        $this->onEnter($bot);
    }

    protected function onCommand(string $command) :void
    {
        $this->bot->onCommand($command, function (Nutgram $bot)
        {
            $this->enter($bot);
        });
    }

    protected function onCallBackQuery(string $pattern) :void
    {
        $this->bot->onCallbackQueryData($pattern, function (Nutgram $bot)
        {
            $this->enter($bot);
        });
    }

    protected function onText(string $text) :void
    {
        $this->bot->onText($text, function (Nutgram $bot)
        {
            $this->enter($bot);
        });
    }

    /**
     * @throws Exception
     */
    public function runEntryHandler() :void
    {
        if (!$this->checkEnterCondition()) {
            return;
        }

        [$handler, $condition] = explode('=', $this->enterCondition);
        $this->handlerDistributor($condition, $handler);
    }

    protected function handlerDistributor(string $condition, string $handler) :void
    {
        if ($handler === 'command') {
            $this->onCommand($condition);
        } else if ($handler === 'text') {
            $this->onText($condition);
        } else if ($handler === 'callBackQuery') {
            $this->onCallBackQuery($condition);
        }
    }

    protected function checkSceneName() : string
    {
        if ($this->name === null) {
            $this->name = $this->manager->generateSceneName(get_class($this));
            return $this->name;
        }

        return $this->name;
    }

    protected function checkEnterCondition() :bool
    {
        if ($this->enterCondition === null) {
            return false;
        }

        $flag = false;
        foreach ($this->availableEnterConditions as $condition)
        {
            if (explode('=', $this->enterCondition)[0] === $condition) {
                $flag = true;
            }
        }

        if (!$flag) {
            throw new Exception('The entryCondition [' . $this->enterCondition . '] is incorrect');
        }

        return true;
    }
}
