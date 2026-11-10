<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DatabaseConfigTest extends TestCase
{
    public static function configurations(): array
    {
        return [
            'accepted disposable database' => ['test_user', 'test-secret', 'disposable', 0, 'disposable'],
            'missing destructive confirmation' => ['test_user', 'test-secret', '', 255, 'Set ACF_TEST_DB_CONFIRM'],
            'wrong destructive confirmation' => ['test_user', 'test-secret', 'other', 255, 'Set ACF_TEST_DB_CONFIRM'],
            'administrator rejected' => ['root', 'test-secret', 'disposable', 255, 'Use the separate test account'],
            'missing password rejected' => ['test_user', '', 'disposable', 255, 'The private INI file requires'],
            'malformed INI rejected without secret leakage' => [
                'test_user',
                "test-secret\"\n[broken",
                'disposable',
                255,
                'Could not parse private database configuration',
            ],
        ];
    }

    /**
     * @dataProvider configurations
     */
    public function testConfigurationStopsUnsafeBootstrapBeforeConnecting(
        string $user,
        #[\SensitiveParameter]
        string $password,
        string $confirmation,
        int $expectedStatus,
        string $expectedOutput,
    ): void {
        $file = tmpfile();
        self::assertNotFalse($file);
        fwrite(
            $file,
            "[client]\nhost=127.0.0.1\nport=3306\nuser={$user}\npassword=\"{$password}\"\ndatabase=disposable\n",
        );

        try {
            [$status, $output] = $this->executeConfig(stream_get_meta_data($file)['uri'], $confirmation);
            self::assertSame($expectedStatus, $status);
            self::assertStringContainsString($expectedOutput, $output);
            self::assertStringNotContainsString('test-secret', $output);
        } finally {
            fclose($file);
        }
    }

    public function testMissingPrivateConfigurationStopsBeforeConnecting(): void
    {
        [$status, $output] = $this->executeConfig('/nonexistent/acf-setup-client.ini');
        self::assertSame(255, $status);
        self::assertStringContainsString('Set ACF_TEST_DB_CONFIG', $output);
    }

    /** @return array{int, string} */
    private function executeConfig(string $path, string $confirmation = ''): array
    {
        $pipes = [];
        $process = proc_open(
            [
                PHP_BINARY,
                '-d',
                'zend.exception_ignore_args=0',
                '-r',
                'require $argv[1]; echo DB_NAME;',
                dirname(__DIR__) . '/Integration/wp-tests-config.php',
            ],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            ['ACF_TEST_DB_CONFIG' => $path, 'ACF_TEST_DB_CONFIRM' => $confirmation],
        );
        self::assertIsResource($process);
        $stdout = $pipes[1] ?? null;
        $stderr = $pipes[2] ?? null;
        self::assertIsResource($stdout);
        self::assertIsResource($stderr);
        $output = stream_get_contents($stdout);
        $errors = stream_get_contents($stderr);
        fclose($stdout);
        fclose($stderr);
        self::assertIsString($output);
        self::assertIsString($errors);

        return [proc_close($process), $output . $errors];
    }
}
