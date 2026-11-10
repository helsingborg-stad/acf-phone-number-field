#!/usr/bin/env php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit();
}

/** @param list<string> $command */
function runBuildCommand(array $command): int
{
    $pipes = [];
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, __DIR__);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start the plugin build command.');
    }
    return proc_close($process);
}

// Keep the parent deployment's flags; --install-npm is a no-op without frontend assets.
if (!in_array('--no-composer', $argv, true)) {
    $status = runBuildCommand([
        'composer',
        'install',
        '--no-dev',
        '--prefer-dist',
        '--no-interaction',
        '--no-progress',
        '--optimize-autoloader',
        '--classmap-authoritative',
    ]);
    if ($status !== 0) {
        exit($status);
    }
}

// This destructive option is only for disposable deployment copies.
if (in_array('--cleanup', $argv, true)) {
    exit(runBuildCommand([
        'rm',
        '-rf',
        '--',
        '.git',
        '.gitignore',
        '.github',
        '.vscode',
        '.cache',
        '.phpunit.cache',
        'tests',
        'scripts',
        'build.php',
        'composer.json',
        'composer.lock',
        'package.json',
        'package-lock.json',
        'phpunit.xml',
        'phpunit.wordpress.xml',
        'phpunit.integration.xml',
        'mago.toml',
    ]));
}
