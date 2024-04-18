<?php

namespace SceneApi;

use Illuminate\Database\Capsule\Manager as Capsule;

class UserRepositoryConfig
{
    const CONFIG_PATH = __DIR__ . '/../configs/user-repository.php';
    protected string $driver = 'mysql';
    protected string $charset = 'utf8';
    protected string $collation = 'utf8_unicode_ci';
    protected ?string $host;
    protected ?int $port;
    protected ?string $dbname;
    protected ?string $username;
    protected ?string $password;

    public function __construct(?string $host = null, ?int $port = null, ?string $dbname = null, ?string $username = null, ?string $password = null)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
    }

    public static function configure(): void
    {
        $conf = self::fromConfig();

        $capsule = new Capsule();

        $capsule->addConnection($conf->toArray());

        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    public function setDriver(string $driver): UserRepositoryConfig
    {
        $this->driver = $driver;

        return $this;
    }

    public function setHost(string $host): UserRepositoryConfig
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(int $port): UserRepositoryConfig
    {
        $this->port = $port;

        return $this;
    }

    public function setDbName(string $dbname): UserRepositoryConfig
    {
        $this->dbname = $dbname;

        return $this;
    }

    public function setUsername(string $username): UserRepositoryConfig
    {
        $this->username = $username;

        return $this;
    }

    public function setPassword(string $password): UserRepositoryConfig
    {
        $this->password = $password;

        return $this;
    }

    protected function validate(): bool
    {
        $flag = true;

        if ($this->host === null) {
            $flag = false;
        }

        if ($this->dbname === null) {
            $flag = false;
        }

        if ($this->username === null) {
            $flag = false;
        }

        if ($this->password === null) {
            $flag = false;
        }

        return $flag;
    }

    public static function fromConfig(): UserRepositoryConfig
    {
        $config = require_once self::CONFIG_PATH;

        return self::fromArray($config);
    }

    public static function fromArray(array $data): UserRepositoryConfig
    {
        $conf = new self();

        $conf
            ->setHost($data['host'])
            ->setDbName($data['dbname'])
            ->setUsername($data['username'])
            ->setPassword($data['password']);

        if ($data['driver'] !== 'mysql') {
            $conf->setDriver($data['driver']);
        }

        if (array_key_exists('port', $data)) {
            $conf->setPort($data['port']);
        }

        return $conf;
    }

    public function toArray(): array
    {
        $data =  [
            'driver'    => $this->driver,
            'charset'   => $this->charset,
            'collation' => $this->collation,
            'host'      => $this->host,
            'database'  => $this->dbname,
            'username'  => $this->username,
            'password'  => $this->password,
            'prefix'    => '',
        ];

        if ($this->driver === 'pgsql') {
            $data['port'] = $this->port;
        }

        return $data;
    }
}