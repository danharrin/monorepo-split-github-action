<?php

declare(strict_types=1);

if ($argc <= 8) {
    note(sprintf('Not enough arguments supplied. Exactly 8 required, but only %d given', $argc - 1));
    exit(0);
} else {
    note('Starting...');
}

// set variables from command line arguments
$packageDirectory = $argv[1];
$splitRepositoryOrganization = $argv[2];
$splitRepositoryName = $argv[3];
$branch = $argv[4];
$tag = $argv[5];
$userEmail = $argv[6];
$userName = $argv[7];
$splitRepositoryHost = $argv[8];

$currentCommitHash = getenv('GITHUB_SHA');


// setup access token to push repository (GitHub or Gitlab supported)
$publicAccessTokens = resolve_public_access_token();


// setup git user + email
if ($userName) {
    exec('git config --global user.name ' . $userName);
}

if ($userEmail) {
    exec('git config --global user.email ' . $userEmail);
}


$cloneDirectory = sys_get_temp_dir() . '/monorepo_split/clone_directory';
$buildDirectory = sys_get_temp_dir() . '/monorepo_split/build_directory';

$hostRepositoryOrganizationName = $splitRepositoryHost. '/' . $splitRepositoryOrganization . '/' . $splitRepositoryName . '.git';

// info
$clonedRepository='https://' . $hostRepositoryOrganizationName;
note(sprintf('Cloning "%s" repository to "%s" directory', $clonedRepository, $cloneDirectory));
exec('git clone -- https://' . $publicAccessTokens . '@' . $hostRepositoryOrganizationName . ' ' . $cloneDirectory);


note('Cleaning destination repository of old files');
// We're only interested in the .git directory, move it to $TARGET_DIR and use it from now on
mkdir($buildDirectory . '/.git', 0777, true);

$copyGitDirectoryCommandLine = sprintf('cp -r %s %s', $cloneDirectory . '/.git', $buildDirectory . '/.git');
exec($copyGitDirectoryCommandLine, $outputLines, $exitCode);

note(sprintf('Files of "%s" directory', $cloneDirectory));
list_directory_files($cloneDirectory);
note(sprintf('Files of "%s" directory', $buildDirectory));
list_directory_files($buildDirectory);


if ($exitCode === 1) {
    die('Command failed');
}


// cleanup old unused data to avoid pushing them
exec('rm -rf ' . $cloneDirectory);


// copy the package directory including all hidden files to the clone dir
// make sure the source dir ends with `/.` so that all contents are copied (including .github etc)
note("Copying contents to git repo of '$branch' branch");
exec(sprintf('cp -ra %s %s', $packageDirectory . '/.', $buildDirectory));

note('Files that will be pushed');
list_directory_files($buildDirectory);


// WARNING! this function happen before we change directory
// if we do this in split repository, the original hash is missing there and it will fail
$commitMessage = createCommitMessage($currentCommitHash);


$formerWorkingDirectory = getcwd();
chdir($buildDirectory);
note(sprintf('Changing directory from "%s" to "%s"', $formerWorkingDirectory, $buildDirectory));


// avoids doing the git commit failing if there are no changes to be commit, see https://stackoverflow.com/a/8123841/1348344
exec('git diff-index --quiet HEAD', $outputLines, $hasChangedFiles);
// remove current repository .git, to avoid committing into same repository
$currentRepositoryGitDirectory = $formerWorkingDirectory . '/.git';
// cleanup old unused data to avoid pushing them
exec('rm -rf ' . $currentRepositoryGitDirectory);


// 1 = changed files
// 0 = no changed files
if ($hasChangedFiles === 1) {
    note('Adding git commit');
    exec('git add .', $outputLines);
    $outputContent = implode(PHP_EOL, $outputLines);
    echo $outputContent . PHP_EOL;

    $message = sprintf('Pushing git commit with "%s" message to "%s"', $commitMessage, $branch);
    note($message);

    exec("git commit --message '$commitMessage'");
    exec('git push --quiet origin ' . $branch);
} else {
    note('No files to change');
}


// restore original directory to avoid nesting WTFs
chdir($formerWorkingDirectory);
note(sprintf('Changing directory from "%s" to "%s"', $buildDirectory, $formerWorkingDirectory));


// push tag if present
if ($tag) {
    $message = sprintf('Publishing "%s"', $tag);
    note($message);

    exec(sprintf('git tag %s -m "%s"', $tag, $message));
    exec('git push --quiet origin ' . $tag);
}


function createCommitMessage(string $commitSha): string
{
    exec("git show -s --format=%B $commitSha", $output);
    return $output[0] ?? '';
}


function note(string $message)
{
    echo PHP_EOL . PHP_EOL . "\033[0;33m[NOTE] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}


function resolve_public_access_token(): string
{
    if (getenv('PAT')) {
        return getenv('PAT');
    }

    if (getenv('GITHUB_TOKEN')) {
        return getenv('GITHUB_TOKEN');
    }

    if (getenv('GITLAB_TOKEN')) {
        return 'oauth2:' . getenv('GITLAB_TOKEN');
    }

    throw new RuntimeException('Public access token is missing, add it via: "PAT", "GITHUB_TOKEN" or "GITLAB_TOKEN" ');
}


function list_directory_files(string $directory) {
    exec('ls -la ' . $directory, $outputLines);
    echo implode(PHP_EOL, $outputLines);
}
