<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * The only databases this suite is permitted to touch.
     *
     * RefreshDatabase drops every table it finds, so a misdirected connection destroys
     * real records — school_erp holds live student, mark and election data. The phpunit
     * config cannot prevent that on its own: PHPUnit's <env force="true"> rewrites
     * getenv() and $_ENV but leaves $_SERVER untouched, and Laravel's env() reads
     * $_SERVER first, so an exported DB_DATABASE silently overrides the config file.
     * This guard is the backstop that does hold.
     */
    private const ALLOWED_TEST_DATABASES = [
        ':memory:',
        'school_erp_ttcheck',
    ];

    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->guardAgainstNonTestDatabase();
    }

    /**
     * Abort before any trait can run migrations against a non-test database.
     *
     * Called from refreshApplication() rather than setUp() deliberately: the base
     * TestCase boots the application and only then calls setUpTraits(), which is where
     * RefreshDatabase does its work. Hooking here means we fail before the first DROP.
     */
    private function guardAgainstNonTestDatabase(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (in_array($database, self::ALLOWED_TEST_DATABASES, true)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Refusing to run tests against database [%s] on connection [%s]. The suite '
            ."uses RefreshDatabase, which drops every table.\nAllowed databases: %s.\n"
            .'Most likely cause: DB_DATABASE or DB_CONNECTION is exported in your shell '
            .'and is overriding phpunit.xml. Unset it, or run the MySQL pass with '
            .'"vendor/bin/phpunit -c phpunit.mysql.xml".',
            $database !== '' ? $database : '(empty)',
            $connection,
            implode(', ', self::ALLOWED_TEST_DATABASES),
        ));
    }
}
