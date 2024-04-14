<?php

namespace SceneApi\Services;

use Exception;
use SceneApi\SceneManager;
use SceneApi\Services\Traits\SceneEntryHandlerRunner;
use SergiX44\Nutgram\Nutgram;

abstract class BaseScene
{
    use SceneEntryHandlerRunner;


    private Nutgram $bot;

    private SceneManager $manager;

    /**
     * @var string|null
     */
    public null|string $name = null;

    /**
     * @var array
     * Middlewares for only this class
     */
    public array $middlewares = [

    ];

    public bool $wasMiddlewaresRun = false;

    /**
     * @var string|null
     * Conditions for entering into scene
     * Available options: command, text
     *
     * @example 'command=start'
     *
     * command
     * text
     * callBackQuery
     */
    public string|null $enterCondition = null;


    public function __construct(Nutgram $bot, SceneManager $manager)
    {
        $this->bot = $bot;
        $this->manager = $manager;
    }

    /**
     * @return void
     * Break down Scene
     */
    public function break(int $userId) :void
    {
        $this->manager->changeUserState($userId, false);
        $this->wasMiddlewaresRun = false;
    }

    /**
     * @return void
     * Jump to next scene
     * @throws Exception
     */
    public function next(Nutgram$bot, string $sceneName) :void
    {
        $this->manager->next($bot, $bot->userId(), $sceneName);
        $this->manager->changeUserState($bot->userId(), true);
        $this->wasMiddlewaresRun = false;
    }

    /**
     * @return void
     * Works out when condition works
     */
    abstract public function onEnter(Nutgram $bot) : void;

    /**
     * @param Nutgram $bot
     * @return void
     *
     * Works out when user sends a request
     */
    abstract public function onQuery(Nutgram $bot) :void;

    /**
     * @param Nutgram $bot
     * @return bool
     */
    public function isSuccess(Nutgram $bot) :bool {
        return true;
    }

    /**
     * @param Nutgram $bot
     * @return void
     *
     * Works out when a failure is handled
     */
    abstract public function onFail(Nutgram $bot) :void;

    abstract public function onSuccess(Nutgram $bot) :void;

    protected function setData(array $data, int $userId) :void
    {
        $this->manager->setData($data, $userId);
    }

    protected function getData(string $key, int $userId) :mixed
    {
        return $this->manager->getData($key, $userId);
    }
}
