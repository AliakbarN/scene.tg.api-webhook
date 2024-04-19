<?php

namespace SceneApi;

use SceneApi\Enums\MiddlewareRunTypes;

class Config
{
    protected ?array $scenes;
    protected ?string $logFile;
    protected ?string $sceneNamespace;
    protected ?string $sceneRootPath;
    protected ?string $middlewareRunType;

    public function __construct(string $logFile = null, ?array $scenes = null, string $sceneNamespace = null, string $sceneRootPath = null, string $middlewareRunType = null)
    {
        $this->logFile = $logFile;
        $this->sceneNamespace = $sceneNamespace;
        $this->sceneRootPath = $sceneRootPath;
        $this->scenes = $scenes;

        if (!$this->checkMiddlewareRunType($middlewareRunType)) {
            $this->middlewareRunType = MiddlewareRunTypes::ONCE->value;
        }
    }

    /**
     * @return string|null
     */
    public function getLogFile(): ?string
    {
        return $this->logFile;
    }

    /**
     * @return array|null
     */
    public function getScenes(): ?array
    {
        return $this->scenes;
    }

    /**
     * @param array|null $scenes
     */
    public function setScenes(?array $scenes): void
    {
        $this->scenes = $scenes;
    }

    public function setLogFile(string $logFile): Config
    {
        $this->logFile = $logFile;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSceneNamespace(): ?string
    {
        return $this->sceneNamespace;
    }

    public function setSceneNamespace(string $sceneNamespace): Config
    {
        $this->sceneNamespace = $sceneNamespace;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSceneRootPath(): ?string
    {
        return $this->sceneRootPath;
    }

    public function setSceneRootPath(string $sceneRootPath): Config
    {
        $this->sceneRootPath = $sceneRootPath;

        return $this;
    }

    /**
     * @return string
     */
    public function getMiddlewareRunType(): string
    {
        return $this->middlewareRunType;
    }

    public function setMiddlewareRunType(string $type): Config
    {
        $this->middlewareRunType = $type;

        return $this;
    }

    protected function checkMiddlewareRunType(?string $type): bool
    {
        return match ($type) {
            MiddlewareRunTypes::ONCE, MiddlewareRunTypes::MULTIPLE => true,
            default => false,
        };
    }
}