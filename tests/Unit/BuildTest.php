<?php

declare(strict_types=1);

namespace AcfPhoneNumberField\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BuildTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $temporaryDirectory = realpath(sys_get_temp_dir());
        self::assertIsString($temporaryDirectory);
        $this->directory = $temporaryDirectory . '/acf-build-' . bin2hex(random_bytes(8));
        mkdir($this->directory . '/bin', 0o700, true);
        copy(dirname(__DIR__, 2) . '/build.php', $this->directory . '/build.php');
        $this->fakeComposer(0);
    }

    public function testBuildInstallsOnlyProductionDependenciesInThePluginDirectory(): void
    {
        self::assertSame(0, $this->runBuild([]));
        $invocation = file_get_contents($this->directory . '/composer-invocation');
        self::assertIsString($invocation);
        self::assertSame(
            [
                $this->directory,
                'install',
                '--no-dev',
                '--prefer-dist',
                '--no-interaction',
                '--no-progress',
                '--optimize-autoloader',
                '--classmap-authoritative',
            ],
            explode("\n", trim($invocation)),
        );
        self::assertFileExists($this->directory . '/build.php');
    }

    public function testParentFlagsSkipComposerAndCleanOnlyTheDisposableCopy(): void
    {
        foreach (['.git', '.cache', '.github', 'tests', 'vendor'] as $directory) {
            mkdir($this->directory . '/' . $directory);
            file_put_contents($this->directory . '/' . $directory . '/sentinel', 'fixture');
        }
        foreach ([
            'composer.json',
            'composer.lock',
            'phpunit.integration.xml',
            'acf-phone-number-field.php',
            'LICENSE',
        ] as $file) {
            file_put_contents($this->directory . '/' . $file, 'fixture');
        }
        mkdir($this->directory . '-external');
        file_put_contents($this->directory . '-external/sentinel', 'keep');
        symlink($this->directory . '-external', $this->directory . '/.vscode');

        self::assertSame(0, $this->runBuild(['--cleanup', '--no-composer', '--install-npm']));

        self::assertFileDoesNotExist($this->directory . '/composer-invocation');
        foreach ([
            '.git',
            '.cache',
            '.github',
            '.vscode',
            'tests',
            'build.php',
            'composer.json',
            'composer.lock',
            'phpunit.integration.xml',
        ] as $path) {
            self::assertFalse(file_exists($this->directory . '/' . $path));
        }
        self::assertFileExists($this->directory . '/vendor/sentinel');
        self::assertFileExists($this->directory . '/acf-phone-number-field.php');
        self::assertFileExists($this->directory . '/LICENSE');
        self::assertFileExists($this->directory . '-external/sentinel');
        self::assertFileExists(dirname(__DIR__, 2) . '/build.php');
    }

    public function testComposerFailureStopsBeforeCleanup(): void
    {
        $this->fakeComposer(23);
        file_put_contents($this->directory . '/composer.json', 'fixture');

        self::assertSame(23, $this->runBuild(['--cleanup']));
        self::assertFileExists($this->directory . '/composer.json');
        self::assertFileExists($this->directory . '/build.php');
    }

    private function fakeComposer(int $status): void
    {
        file_put_contents(
            $this->directory . '/bin/composer',
            "#!/bin/sh\nprintf '%s\\n' \"\$PWD\" \"\$@\" > composer-invocation\nexit {$status}\n",
        );
        chmod($this->directory . '/bin/composer', 0o700);
    }

    /** @param list<string> $flags */
    private function runBuild(array $flags): int
    {
        $pipes = [];
        $process = proc_open(
            [PHP_BINARY, $this->directory . '/build.php', ...$flags],
            [1 => ['file', $this->directory . '/output.log', 'w'], 2 => ['file', $this->directory . '/error.log', 'w']],
            $pipes,
            sys_get_temp_dir(),
            ['PATH' => $this->directory . '/bin:' . (getenv('PATH') ?: '')],
        );
        self::assertIsResource($process);
        return proc_close($process);
    }

    protected function tearDown(): void
    {
        $pipes = [];
        $process = proc_open(['rm', '-rf', '--', $this->directory, $this->directory . '-external'], [], $pipes);
        if (is_resource($process)) {
            proc_close($process);
        }
        parent::tearDown();
    }
}
