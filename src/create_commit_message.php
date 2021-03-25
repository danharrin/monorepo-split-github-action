<?php

declare(strict_types=1);

// WARNING! this must happen in the main directory repository, not the split one
// if we do this in split repository, the original hash is missing there and it will fail

function createCommitMessage(string $commitSha): string
{
    exec("git show -s --format=%B $commitSha", $output);
    $bareMessage = $output[0] ?? '';
    return $bareMessage . PHP_EOL;
}

$envs = getenv();

echo createCommitMessage($envs['GITHUB_SHA']);
