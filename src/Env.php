<?php

namespace Atom\Framework;

use Closure;
use Dotenv\Dotenv;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;
use InvalidArgumentException;
use PhpOption\Option;

class Env
{
    const PRODUCTION = "production";
    const TESTING = "testing";
    const DEV = "development";
    const STAGING = "staging";

    protected static array $ALLOWED_ENV = [self::PRODUCTION, self::TESTING, self::DEV, self::STAGING];

    /**
     * @var string
     */
    private string $env;
    /**
     * @var Dotenv|null
     */
    protected ?Dotenv $dotEnv = null;

    /**
     * @var string
     */
    private string $path;

    private static string $defaultEnv = self::DEV;

    /**
     * @var RepositoryInterface|null
     */
    private ?RepositoryInterface $repository = null;

    public static function default(string $env)
    {
        self::validateEnv($env);
        self::$defaultEnv = $env;
    }

    public static function getDefaultEnv(): string
    {
        return self::$defaultEnv;
    }

    public static function addAllowedEnv(string $env)
    {
        if (!in_array($env, self::$ALLOWED_ENV)) {
            self::$ALLOWED_ENV[] = $env;
        }
    }

    public static function create(string $path, ?string $env = null): Env
    {
        return new self($path, $env);
    }

    public function __construct(string $path, ?string $env = null)
    {
        $env = $env ?? self::$defaultEnv;
        $this->validateEnv($env);
        $this->path = $path;
        $this->env = $env;
    }

    /**
     * @param $expected
     * @return bool
     */
    public function is($expected): bool
    {
        return $this->env === $expected;
    }

    /**
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->is(self::PRODUCTION);
    }

    /**
     * @return bool
     */
    public function isTesting(): bool
    {
        return $this->is(self::TESTING);
    }

    /**
     * @return bool
     */
    public function isDev(): bool
    {
        return $this->is(self::DEV);
    }

    /**
     * @return bool
     */
    public function isStaging(): bool
    {
        return $this->is(self::STAGING);
    }

    /**
     * @return Dotenv
     */
    public function dotEnv(): Dotenv
    {
        if ($this->dotEnv === null) {
            $this->dotEnv = Dotenv::create($this->getRepository(), $this->path, [
                ".env." . strtolower($this->env),
                ".env",
            ]);
        }
        return $this->dotEnv;
    }

    /**
     * @param string $key
     * @param $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return Option::fromValue($this->getRepository()->get($key))
            ->map(function ($value) {
                switch (strtolower($value)) {
                    case 'true':
                    case '(true)':
                        return true;
                    case 'false':
                    case '(false)':
                        return false;
                    case 'empty':
                    case '(empty)':
                        return '';
                    case 'null':
                    case '(null)':
                        return null;
                }
                if (preg_match('/\A([\'"])(.*)\1\z/', $value, $matches)) {
                    return $matches[2];
                }
                return $value;
            })
            ->getOrCall(function () use ($default) {
                return $default instanceof Closure ? $default() : $default;
            });
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }


    public function __toString(): string
    {
        return $this->env;
    }

    /**
     * @return RepositoryInterface
     */
    private function getRepository(): RepositoryInterface
    {
        if ($this->repository == null) {
            $factory = RepositoryBuilder::createWithDefaultAdapters();
            $this->repository = $factory->immutable()->make();
        }
        return $this->repository;
    }

    /**
     * @param string $env
     */
    private static function validateEnv(string $env)
    {
        if (!in_array($env, self::$ALLOWED_ENV)) {
            throw new InvalidArgumentException(
                "ENV should be either on of this values [" . implode(",", self::$ALLOWED_ENV) . "]"
            );
        }
    }
}
