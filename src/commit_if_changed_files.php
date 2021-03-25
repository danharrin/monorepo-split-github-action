<?php

function createCommitMessage(string $commitSha): string
{
    exec("git show -s --format=%B $commitSha", $output);
    return $output[0] ?? '';
}

function note(string $message) {
    echo PHP_EOL . "\033[0;33m[NOTE] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}


// setup GitHub envs to variables
$envs = getenv();


exec('git add .', $output);
$outputContent = implode(PHP_EOL, $output);
echo $outputContent . PHP_EOL;


exec('git status', $output);
$outputContent = implode(PHP_EOL, $output);
echo $outputContent . PHP_EOL;

// avoids doing the git commit failing if there are no changes to be commit, see https://stackoverflow.com/a/8123841/1348344
exec('git diff-index --quiet HEAD', $output, $hasChangedFiles);

// debug
var_dump($hasChangedFiles);

// 1 = changed files
// 0 = no changed files
//if ($hasChangedFiles === 1) {
    $commitSha = $envs['GITHUB_SHA'];

    note('Adding git commit');
    note('Current commit sha: ' . $commitSha);

    $commitMessage = createCommitMessage($commitSha);

    exec("git commit --message '$commitMessage'");

    note('Pushing git commit with "' . $commitMessage . '" message');
    $branch = $envs['BRANCH'];
    exec('git push --quiet origin ' . $branch);
//} else {
//    note('No files to change');
//}
