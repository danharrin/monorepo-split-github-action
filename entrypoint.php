<?php

declare(strict_types=1);

use Symplify\MonorepoSplit\Config;
use Symplify\MonorepoSplit\ConfigFactory;
use Symplify\MonorepoSplit\Exception\ConfigurationException;

require_once __DIR__ . '/src/autoload.php';

note('Resolving configuration...');

$configFactory = new ConfigFactory();
try {
    $config = $configFactory->create(getenv());
} catch (ConfigurationException $configurationException) {
    error($configurationException->getMessage());
    exit(0);
}

setupGitCredentials($config);

$randomName = bin2hex(random_bytes(8));
$cloneDirectory = sys_get_temp_dir() . '/monorepo_split/clone_directory/' . $randomName;
$buildDirectory = sys_get_temp_dir() . '/monorepo_split/build_directory/' . $randomName;

$hostRepositoryOrganizationName = $config->getGitRepository();

// info
$clonedRepository = 'https://' . $hostRepositoryOrganizationName;
$cloningMessage = sprintf('Cloning "%s" repository to "%s" directory', $clonedRepository, $cloneDirectory);
note($cloningMessage);

$lastVersion = file_get_contents('packages/'.$config->getPackageDirectory().'/composer.json');
$lastVersion = json_decode($lastVersion, true);
$lastVersion = $lastVersion['version'];

$commandLine = 'git clone -- https://' . $config->getAccessToken() . '@' . $hostRepositoryOrganizationName . ' ' . $cloneDirectory;
exec_with_note($commandLine);

$baseDir = getcwd();

chdir($cloneDirectory);

exec_with_output_print('git fetch');

note(sprintf('Trying to checkout %s branch', $config->getBranch()));

// if the given branch doesn't exist it returns empty string
$branchSwitchedSuccessfully = exec(sprintf('git checkout %s', $config->getBranch())) !== '';

// if the branch doesn't exist we creat it and push to origin
// otherwise we just checkout to the given branch
if (! $branchSwitchedSuccessfully) {
    note(sprintf('Creating branch "%s" as it doesn\'t exist', $config->getBranch()));

    exec_with_output_print(sprintf('git checkout -b %s', $config->getBranch()));
    exec_with_output_print(sprintf('git push --quiet origin %s', $config->getBranch()));
}

// While we're in the cloned repository folder, retrieve the commit hash of the last tag,
// and the most recent commit hash, for later use to determine if a new tag should be pushed


$lastTag = getLatestTag();
$lastTag = $lastVersion ?? $lastTag;
$latestTagCommitHash = $lastTag ? getTagCommitHash($lastTag) : '';
$latestCommitHash = getLatestCommitHash();

chdir($baseDir);

note('Cleaning destination repository of old files');
// We're only interested in the .git directory, move it to $TARGET_DIR and use it from now on
mkdir($buildDirectory . '/.git', 0777, true);

$copyGitDirectoryCommandLine = sprintf('cp -r %s %s', $cloneDirectory . '/.git', $buildDirectory);
exec($copyGitDirectoryCommandLine, $outputLines, $exitCode);

if ($exitCode === 1) {
    die('Command failed');
}


// cleanup old unused data to avoid pushing them
exec('rm -rf ' . $cloneDirectory);
// exec('rm -rf .git');


// copy the package directory including all hidden files to the clone dir
// make sure the source dir ends with `/.` so that all contents are copied (including .github etc)
$copyMessage = sprintf('Copying contents to git repo of "%s" branch', $config->getCommitHash());
note($copyMessage);
$commandLine = sprintf('cp -ra %s %s', $config->getPackageDirectory() . '/.', $buildDirectory);
exec($commandLine);

note('Files that will be pushed');

list_directory_files($buildDirectory);
setSafeDirectory('*');

// WARNING! this function happen before we change directory
// if we do this in split repository, the original hash is missing there and it will fail
$commitMessage = createCommitMessage($config->getCommitHash());


$formerWorkingDirectory = getcwd();
chdir($buildDirectory);

$restoreChdirMessage = sprintf('Changing directory from "%s" to "%s"', $formerWorkingDirectory, $buildDirectory);
note($restoreChdirMessage);


// avoids doing the git commit failing if there are no changes to be commit, see https://stackoverflow.com/a/8123841/1348344
exec_with_output_print('git status');

// "status --porcelain" retrieves all modified files, no matter if they are newly created or not,
// when "diff-index --quiet HEAD" only checks files that were already present in the project.
exec('git status --porcelain', $changedFiles);

// $changedFiles is an array that contains the list of modified files, and is empty if there are no changes.

if ($changedFiles) {
    note('Adding git commit');

    exec_with_output_print('git add .');

    $message = sprintf('Pushing git commit with "%s" message to "%s"', $commitMessage, $config->getBranch());
    note($message);

    exec("git commit --message '{$commitMessage}'");
    exec('git push --quiet origin ' . $config->getBranch());

    // Update last commit hash since we just pushed a new commit
    $latestCommitHash = getLatestCommitHash();
} else {
    note('No files to change');
}


// push tag if present
$currentTag = $lastVersion;

if ($currentTag) {
    // If the tag looks like a dev tag (e.g., dev-*) and already exists, delete it locally and remotely first
    if (preg_match('/^dev-/', $currentTag)) {
        // Check if tag exists locally
        exec('git tag', $localTags);
        if (in_array($currentTag, $localTags)) {
            note(sprintf('Deleting existing local tag "%s"', $currentTag));
            exec_with_note('git tag -d ' . $currentTag);
        }
        // Check if tag exists remotely
        exec('git ls-remote --tags origin', $remoteTagsRaw);
        $remoteTagExists = false;
        foreach ($remoteTagsRaw as $line) {
            if (preg_match('/refs\\/tags\\/' . preg_quote($currentTag, '/') . '$/', $line)) {
                $remoteTagExists = true;
                break;
            }
        }
        if ($remoteTagExists) {
            note(sprintf('Deleting existing remote tag "%s"', $currentTag));
            exec_with_note('git push --delete origin ' . $currentTag);
        }
    }
    $message = sprintf('Publishing "%s"', $currentTag);
    note($message);

    $commandLine = sprintf('git tag %s -m "%s"', $currentTag, $message);
    exec_with_note($commandLine);

    exec_with_note('git push --quiet origin ' . $currentTag);
}


// restore original directory to avoid nesting WTFs
chdir($formerWorkingDirectory);
$chdirMessage = sprintf('Changing directory from "%s" to "%s"', $buildDirectory, $formerWorkingDirectory);
note($chdirMessage);

function setSafeDirectory(string $directoryFullPath): void
{
    exec('git config --global --add safe.directory "' . $directoryFullPath .'"');
}

function createCommitMessage(string $commitSha): string
{
    exec("git show -s --format=%B {$commitSha}", $outputLines);
    return $outputLines[0] ?? '';
}


function note(string $message): void
{
    echo PHP_EOL . PHP_EOL . "\033[0;33m[NOTE] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}

function error(string $message): void
{
    echo PHP_EOL . PHP_EOL . "\033[0;31m[ERROR] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}




function list_directory_files(string $directory): void
{
    exec_with_output_print('ls -la ' . $directory);
}


/********************* helper functions *********************/

function exec_with_note(string $commandLine): void
{
    note('Running: ' . $commandLine);
    exec($commandLine);
}


function exec_with_output_print(string $commandLine): void
{
    exec($commandLine, $outputLines);
    echo implode(PHP_EOL, $outputLines);
}


function setupGitCredentials(Config $config): void
{
    if ($config->getUserName()) {
        exec('git config --global user.name ' . $config->getUserName());
    }

    if ($config->getUserEmail()) {
        exec('git config --global user.email ' . $config->getUserEmail());
    }
}

/********************* tag-related helper functions *********************/

function is_patch(string $version): bool
{
    $version = explode('.', $version);
    if (count($version) !== 3) {
        $version[] = 0;
    }

    if ($version[1] === '0' && $version[2] === '0') {
        return false; // major version
    }

    if ($version[2] === '0') {
        return false;  // minor version
    }

    return true;
}

function getLatestTag(): string
{
    exec('git describe --tags --abbrev=0', $outputLines);

    return $outputLines[0] ?? '';
}

function getTagCommitHash(string $tag): string
{
    exec("git rev-list -n 1 {$tag}", $outputLines);

    return $outputLines[0] ?? '';
}

function getLatestCommitHash(): string
{
    exec('git rev-parse HEAD', $outputLines);

    return $outputLines[0] ?? '';
}
