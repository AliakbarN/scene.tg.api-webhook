<?php

namespace SceneApi;

use Exception;
use SceneApi\Services\BaseScene;
use SceneApi\Services\Helpers\GetOption;
use SceneApi\Services\Helpers\GetScenes;
use SceneApi\Services\Logger;
use SceneApi\Services\ODT\UserState;
use SceneApi\Traits\CanManageUserData;
use SceneApi\Traits\CanManageUsers;
use SceneApi\Traits\MiddlewaresManager;
use SergiX44\Nutgram\Nutgram;

class SceneManager
{

    use MiddlewaresManager, CanManageUsers, CanManageUserData;


    protected GetOption $option;

    /**
     * @var array
     * Scenes list
     */
    protected array $scenes = [];

    /**
     * @var BaseScene[]
     */
    protected array $initiatedScenes = [];

    /**
     * @var ?UserState
     */
    protected ?UserState $user;

    protected Nutgram $bot;
    public Logger $logger;


    public function __construct(Nutgram $bot, ?array $scenes = null, array $options = null, ?string $logFile = null)
    {
        $this->bot = $bot;

        $this->option = new GetOption($options);

        $logFile = $logFile !== null ?: __DIR__ . '/../scenes-logs.txt';

        $this->logger = new Logger($logFile);

        UserRepositoryConfig::configure();

        if ($scenes === null) {
            if (count($this->scenes) === 0) {
                if ($this->option->get('sceneNameSpace') & $this->option->get('scenesDirPath')) {
                    $this->scenes = GetScenes::getSceneClasses($this->option->get('scenesDirPath'), $this->option->get('sceneNameSpace'));
                }
            }
        } else {
            $this->scenes = $scenes;
        }
    }

    /**
     * Entry point
     *
     * @throws Exception
     */
    public function process(): void
    {
        if (!$this->checkClasses()) {
            return;
        }

        foreach ($this->scenes as $key => $scene)
        {
            $this->initiateSceneClass($scene);

            $this->checkMiddlewares($this->initiatedScenes[$key]);

            $this->initiatedScenes[$key]->runEntryHandler();
        }

        $this->handle();
    }

    /**
     * Run manager (scene chain) with a specific scene
     *
     * @param Nutgram $bot
     * @param mixed $sceneClassName
     * @return void
     * @throws SceneManagerException
     */
    public function start(Nutgram $bot, mixed $sceneClassName): void
    {
        if (!$this->checkClasses(true, $sceneClassName)) {
            return;
        }

        $scene = $this->initiateSceneClass($sceneClassName, true);

        $this->addUser($bot->userId(), $scene->name, false);
        $scene->onEnter($bot);
    }

    /**
     * Starts manager (scene chain) with a specific scene
     *
     * @param Nutgram $bot
     * @return void
     */
    public function startByScene(Nutgram $bot): void
    {
        $this->retrieveUser();

        $scene = $this->findSceneByName($this->getUser()->sceneName);

        $scene->onEnter($bot);
    }

    /**
     * Runs handler
     *
     * @return void
     */
    protected function handle(): void
    {
        $this->bot->onMessage(function (Nutgram $bot)
        {
            $this->logger->info('Handle...');
            error_log('Handle...');

            $this->retrieveUser();

            if (!$this->validateUser()) {
                return;
            }

            $scene = $this->findSceneByName($this->getUser()->sceneName);

            if ($scene === null) {
                $this->logger->error('Something went wrong. /n/r' . $scene .' = null.');
            }

            if (!$this->getUser()->isActive) {
                return;
            }

            if ($this->getUser()->isEnter) {
                $scene->onEnter($bot);
                $this->changeUserSceneState(false);

                $this->backupUser();

                return;
            }

            if (!$scene->wasMiddlewaresRun) {
                $breakDownChain = $this->manageMiddlewares($bot, $this->initiateMiddlewares($scene->middlewares));

                if ($this->option->get('runMiddlewaresOnce')) {
                    $scene->wasMiddlewaresRun = true;
                }

                if ($breakDownChain === true) {
                    return;
                }
            }

            if (!$scene->isSuccess($bot)) {
                $scene->onFail($bot);
                return;
            }

            $scene->onQuery($bot);
            $scene->onSuccess($bot);

            $this->backupUser();
        });
    }

    /**
     * Initiates scenes
     *
     * @param mixed $className
     * @param bool $getResult
     * @return BaseScene|null
     */
    protected function initiateSceneClass(mixed $className, bool $getResult = false): BaseScene|null
    {
        $newInitiatedScene = new $className($this->bot, $this);

        if ($newInitiatedScene->name === null) {
            $newInitiatedScene->name = $this->generateSceneName(get_class($newInitiatedScene));
        }

        $flag = true;

        foreach ($this->initiatedScenes as $initiatedScene)
        {
            if ($initiatedScene->name === $newInitiatedScene->name) {
                $flag = false;
            }
        }

        if ($flag) {
            $this->initiatedScenes[] = $newInitiatedScene;
        }

        if ($getResult) {
            return $newInitiatedScene;
        }

        return null;
    }


    /**
     * Validates scene classes
     *
     * @param bool $onlyOne
     * @param string|null $sceneName
     * @return bool
     * @throws SceneManagerException
     */
    protected function checkClasses(bool $onlyOne = false, string $sceneName = null): bool
    {
        if ($onlyOne) {
            if ($sceneName === null) {
                $this->logger->error('className argument missed in checkClass');
            } else {
                if (!is_a($sceneName, BaseScene::class, true)) {
                    $this->logger->error('The class [' . $sceneName .'] is not extended by BaseScene');
                } else {
                    return true;
                }
            }
        }

        foreach ($this->scenes as $scene)
        {
            if (!is_a($scene, BaseScene::class, true)) {
                $this->logger->error('The class [' . $scene .'] is not extended by BaseScene');
            }
        }

        if (count($this->scenes) === 0) {
            return false;
        }

        return true;
    }


    /**
     * Finds scene class by name
     *
     * @param string $sceneName
     * @return BaseScene|null
     */
    protected function findSceneByName(string $sceneName): BaseScene|null
    {
        foreach ($this->initiatedScenes as $scene)
        {
            if ($scene->name === null) {
                $scene->name = $this->generateSceneName($scene::class);
            }

            if ($scene->name === $sceneName) {
                return $scene;
            }
        }

        return null;
    }

    /**
     * Generates scene name if not provided
     *
     * @param string $sceneClassName
     * @return string
     */
    public function generateSceneName(string $sceneClassName): string
    {
        $arr = explode('\\', $sceneClassName);
        $sceneClassName = end($arr);
        $sceneClassName[0] = strtolower($sceneClassName[0]);
        return $sceneClassName;
    }

    /**
     * Changes user's scene
     *
     * @param Nutgram $bot
     * @param string $sceneName
     * @return void
     * @throws SceneManagerException
     */
    public function next(Nutgram $bot, string $sceneName): void
    {
        $this->changeUserScene($sceneName);
//        $this->users[$userId]->sceneName = $sceneName;
        $this->changeUserSceneState(false);
//        $this->users[$userId]->isEnter = false;

        $scene = $this->findSceneByName($sceneName);

        if ($scene === null) {
            $this->logger->error('Something went wrong. The problem might be caused by incorrect scene name');
        }

        $scene->onEnter($bot);
    }
}
