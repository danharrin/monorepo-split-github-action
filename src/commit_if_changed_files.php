<?php

declare(strict_types=1);

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

require_once __DIR__ . '/../vendor/autoload.php';

// setup GitHub envs to variables
$envs = getenv();

$commitSha = $envs['GITHUB_SHA'];
$branch = $envs['BRANCH'];

function createCommitMessage(string $commitSha): string
{
    exec("git show -s --format=%B $commitSha", $output);
    return $output[0] ?? '';
}

$commitMessage = createCommitMessage($commitSha);


// avoids doing the git commit failing if there are no changes to be commit, see https://stackoverflow.com/a/8123841/1348344
exec('git diff-index --quiet HEAD', $output, $hasChangedFiles);

$symfonyStyle = new SymfonyStyle(new ArgvInput(), new ConsoleOutput());

// 1 = changed files
// 0 = no changed files
if ($hasChangedFiles === 1) {
    $symfonyStyle->note('Adding git commit');

    exec('git add .');
    exec("git commit --message '$commitMessage'");

    $symfonyStyle->note('Pushing git commit with "' . $commitMessage . '" message');
    exec('git push --quiet origin $branch');
} else {
    $symfonyStyle->warning('No files to change');
}
