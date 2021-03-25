<?php

declare(strict_types=1);

// setup GitHub envs to variables
$envs = getenv();

$commitSha = $envs['GITHUB_SHA'];
$branch = $envs['BRANCH'];

function createCommitMessage(string $commitSha): string
{
    exec("git show -s --format=%B $commitSha", $output);
    return $output[0] ?? '';
}

function note(string $message) {
    echo PHP_EOL . "\033[0;33m[NOTE]  " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}

$commitMessage = createCommitMessage($commitSha);


// avoids doing the git commit failing if there are no changes to be commit, see https://stackoverflow.com/a/8123841/1348344
exec('git diff-index --quiet HEAD', $output, $hasChangedFiles);

var_dump($hasChangedFiles);

// 1 = changed files
// 0 = no changed files
if ($hasChangedFiles === 1) {
    note('Adding git commit');

    exec('git add .');
    exec("git commit --message '$commitMessage'");

    note('Pushing git commit with "' . $commitMessage . '" message');
    exec('git push --quiet origin $branch');
} else {
    note('No files to change');
}
