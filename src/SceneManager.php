<?php

namespace SceneApi;

use Exception;
use SceneApi\Enums\MiddlewareRunTypes;
use SceneApi\Services\BaseScene;
use SceneApi\Services\Helpers\GetOption;
use SceneApi\Services\Helpers\GetScenes;
use SceneApi\Services\Logger;
use SceneApi\Services\ODT\UserState;
use SceneApi\Traits\CanManageUsers;
use SceneApi\Traits\MiddlewaresManager;
use SergiX44\Nutgram\Nutgram;

class SceneManager
{

    use MiddlewaresManager, CanManageUsers;


    protected Config $config;

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


    public function __construct(Nutgram $bot, Config $config, ?array $scenes = null)
    {
        $this->bot = $bot;

        $this->config = $config;

        $logFile = $this->config->getLogFile() ?? __DIR__ . '/../logs.log';

        $this->logger = new Logger($logFile);

        UserRepositoryConfig::configure();

        if ($scenes === null) {

            if ($this->config->getScenes() !== null) {
                $this->scenes = $this->config->getScenes();
            } else if ($this->config->getSceneRootPath() !== null && $this->config->getSceneNamespace() !== null) {
                $this->scenes = GetScenes::get($this->config->getSceneRootPath(), $this->config->getSceneNamespace());
            }
        } else {
            $this->scenes = [];
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
     * @param string|null $sceneName
     * @return void
     * @throws SceneManagerException
     */
    public function startByScene(Nutgram $bot, string $sceneName = null): void
    {
        if (!$this->checkUserExists($bot->userId())) {
            if ($sceneName === null) {
                throw new SceneManagerException('Provide the scene name');
            }

            $this->addUser($sceneName, false);
        } else {
            $this->retrieveUser();
        }

        $sceneName = $sceneName ?? $this->getUser()->sceneName;

        $scene = $this->findSceneByName($sceneName);

        $middlewares = $this->initiateMiddlewares($scene->middlewares);
        $breakDownChain = $this->manageMiddlewares($bot, $middlewares);

        // If middleware chain was not broken, continue to onEnter
        if (!$breakDownChain) {
            $scene->onEnter($bot);
        }
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

            if (!$scene->wasMiddlewaresRun) {
                if ($this->getUser()->isEnter) {
                    $breakDownChain = $this->manageMiddlewares($bot, $this->initiateMiddlewares($scene->middlewares));

                    if ($this->config->getMiddlewareRunType() === MiddlewareRunTypes::ONCE->value) {
                        $scene->wasMiddlewaresRun = true;
                    }

                    if ($breakDownChain === true) {
                        return;
                    }
                }
            }

            if ($this->getUser()->isEnter) {
                $scene->onEnter($bot);
                $this->changeUserSceneState(false);

                $this->backupUser();

                return;
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
     */
    protected function checkClasses(bool $onlyOne = false, string $sceneName = null): bool
    {
        if ($onlyOne) {
            if ($sceneName === null) {
                $this->logger->error('$sceneName argument missed in checkClass');
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

        $this->logger->error('Something went wrong. The problem might be caused by incorrect scene name');

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
     */
    public function next(Nutgram $bot, string $sceneName): void
    {
        $this->changeUserScene($sceneName);
        $this->changeUserSceneState(false);

        $scene = $this->findSceneByName($sceneName);

        $middlewares = $this->initiateMiddlewares($scene->middlewares);
        $breakDownChain = $this->manageMiddlewares($bot, $middlewares);

        // If middleware chain was not broken, continue to onEnter
        if (!$breakDownChain) {
            $scene->onEnter($bot);
        }
    }
}
