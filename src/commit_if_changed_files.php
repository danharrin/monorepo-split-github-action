<?php

declare(strict_types=1);


// use like: php/commit_if_changed_files.php "<repository path to push>" "<commit sha-1>" "<branch>"
$repositoryPathToPush = $argv[0];
$currentCommitHash = $argv[1];
$branch = $argv[2];

echo 'repo: ';
var_dump($repositoryPathToPush);
echo 'commit hash: ';
var_dump($currentCommitHash);
echo 'branch: ';
var_dump($branch);


// WARNING! this function happen before we change directory
// if we do this in split repository, the original hash is missing there and it will fail
$commitMessage = createCommitMessage($currentCommitHash);


$formerWorkingDirectory = getcwd();
chdir($repositoryPathToPush);


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
if ($hasChangedFiles === 1) {
    note('Adding git commit');

    note('Pushing git commit with "' . $commitMessage . '" message');

    exec("git commit --message '$commitMessage'");
    exec('git push --quiet origin ' . $branch);
} else {
    note('No files to change');
}


// restore original directory to avoid nesting WTFs
chdir($formerWorkingDirectory);




// functions

function createCommitMessage(string $commitSha): string
{
    exec("git show -s --format=%B $commitSha", $output);
    $bareMessage = $output[0] ?? '';
    return $bareMessage . PHP_EOL;
}


function note(string $message) {
    echo PHP_EOL . "\033[0;33m[NOTE] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}


