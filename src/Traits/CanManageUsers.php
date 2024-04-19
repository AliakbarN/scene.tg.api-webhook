<?php

namespace SceneApi\Traits;

use SceneApi\Models\User;
use SceneApi\Models\UserData;
use SceneApi\Services\ODT\UserState;

trait CanManageUsers
{

    use CanManageUserData;

    protected function retrieveUser(): void
    {
        $user = User::where(User::ID, $this->bot->userId())->first();

        if ($user === null) {
            $this->logger->error('The user with tg id = ' . $this->bot->userId() . ' not found');
        }

        $this->user = UserState::fromModel($user);

        $this->setUserData($user->userData);
    }

    protected function backupUser(): void
    {
        $user = User::where(User::ID, $this->bot->userId())->first();
        $currentUser = $this->getUser();

        $user->scene = $currentUser->sceneName;
        $user->is_active = $currentUser->isActive;
        $user->is_enter = $currentUser->isEnter;

        $user->save();

        $user->userData()->update($this->getUserDataForUpdate());
    }

    protected function getUser(): UserState
    {
        return $this->user;
    }

    public function checkUserExists(int $tgId): bool
    {
        $user = User::where(User::ID, $tgId)->first();

        if ($user === null) {
            return false;
        }

        return true;
    }

    protected function validateUser(): bool
    {
        return $this->user->isActive;
    }

    public function addUser(string $sceneName, bool $isEnter) :void
    {
        $user = new UserState($sceneName, $this->bot->userId(), true, $isEnter);
        $userData = new UserData();

        $this->user = $user;

        $userForSave = User::fromDTO($user);

        $userForSave->save();
        $userForSave->userData()->save($userData);
    }

    public function deleteUser(int $userId) :void
    {
        $this->user = null;

        /** @var User $user */
        $user = User::where(User::ID, $userId)->first();

        $user?->delete();
    }

    public function changeUserState(bool $state): void
    {
        $this->user->isActive = $state;
    }

    protected function changeUserSceneState(bool $state): void
    {
        $this->user->isEnter = $state;
    }

    protected function changeUserScene(string $scene): void
    {
        $this->user->sceneName = $scene;
    }
}