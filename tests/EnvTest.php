<?php


namespace Atom\Framework\Test;

use Atom\Framework\Env;
use Dotenv\Dotenv;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    public function testCreateConstructor()
    {
        $env = Env::create("foo", Env::PRODUCTION);
        $this->assertTrue($env->isProduction());
        $this->assertFalse($env->isStaging());
    }

    public function testItCreateWithDefaultEnv()
    {
        $env = new Env("foo");
        $this->assertEquals(Env::getDefaultEnv(), (string)$env);
    }

    public function testItThrowsWithUnhallowedEnv()
    {
        $this->expectException(InvalidArgumentException::class);
        new Env("foo", "i'm invalid");
    }

    public function testCustomEnv()
    {
        Env::addAllowedEnv($name = "ALLOWED");
        $env = new Env("foo", $name);
        $this->assertTrue($env->is($name));
    }

    public function testIs()
    {
        $env = new Env("foo", Env::PRODUCTION);
        $this->assertTrue($env->is(Env::PRODUCTION));
        $this->assertFalse($env->is(Env::DEV));
    }

    public function testIsEnv()
    {
        $env = new Env("foo", Env::PRODUCTION);
        $this->assertTrue($env->isProduction());
        $this->assertFalse($env->isStaging());

        $env = new Env("foo", Env::DEV);
        $this->assertTrue($env->isDev());
        $this->assertFalse($env->isProduction());

        $env = new Env("foo", Env::TESTING);
        $this->assertTrue($env->isTesting());
        $this->assertFalse($env->isDev());

        $env = new Env("foo", Env::STAGING);
        $this->assertTrue($env->isStaging());
        $this->assertFalse($env->isDev());
    }

    public function testDotEnv()
    {
        $env = Env::create("foo");
        $this->assertInstanceOf(Dotenv::class, $dotEnv = $env->dotEnv());
        $this->assertEquals($dotEnv, $env->dotEnv());
    }

    public function testGet()
    {
        $env = Env::create("foo");
        $this->assertNull($env->get("foo"));
        $cases = [
            true => ["(true)", "true"],
            false => ["(false)", "false"],
            "" => ["empty", "(empty)"],
            null => ["null", '(null)'],
            "bar" => ["'bar'", '"bar"']
        ];
        foreach ($cases as $expected => $tests) {
            foreach ($tests as $test) {
                $_ENV["test"] = $test;
                $this->assertEquals($expected, $env->get("test"));
            }
        }
        unset($_ENV["test"]);
        $this->assertNull($env->get("bar"));
        $this->assertEquals("foo", $env->get("idontexists", "foo"));
        $this->assertEquals("foo", $env->get("idontexists", fn() => "foo"));
    }

    public function testHas()
    {
        $_ENV["test"] = "foo";
        $env = Env::create("foo");
        $this->assertTrue($env->has("test"));
        $this->assertFalse($env->has("bar"));
        unset($_ENV["test"]);
    }

    public function testToString()
    {
        $env = Env::create("foo", $expected = Env::STAGING);
        $this->assertEquals((string)$env, $expected);
    }
}
