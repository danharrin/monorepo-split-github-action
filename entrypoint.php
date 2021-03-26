<?php

declare(strict_types=1);

if ($argc < 8) {
    note("Not enough arguments supplied. 8 required");
    exit(0);
}

note('Starts');

// set parameter from command line
$PACKAGE_DIRECTORY = $argv[1];
$SPLIT_REPOSITORY_ORGANIZATION = $argv[2];
$SPLIT_REPOSITORY_NAME = $argv[3];
$BRANCH = $argv[4];
$TAG = $argv[5];
$USER_EMAIL = $argv[6];
$USER_NAME = $argv[7];
$SPLIT_REPOSITORY_HOST = $argv[8];


# setup access token so went push repository

// public access token
$pat = null;
$hostPrefix = null;
if (getenv('GITHUB_TOKEN')) {
    $pat = getenv('GITHUB_TOKEN');
} elseif (getenv('GITLAB_TOKEN')) {
    $pat = getenv('GITLAB_TOKEN');
    $hostPrefix = 'oauth2:';
}



// setup git user + email
if (getenv('USER_EMAIL')) {
    $userEmail = getenv('USER_EMAIL');
    exec('git config --global user.email ' . $userEmail);
}

if (getenv('USER_NAME')) {
    $userEmail = getenv('USER_NAME');
    exec('git config --global user.name ' . $userEmail);
}


$CLONE_DIR='clone_directory';
$TARGET_DIR='build_directory';

$HOST_REPOSITORY_ORGANIZATION_NAME=$SPLIT_REPOSITORY_HOST. '/' . $SPLIT_REPOSITORY_ORGANIZATION . '/' . $SPLIT_REPOSITORY_NAME . '.git';

$CLONED_REPOSITORY='https://' . $HOST_REPOSITORY_ORGANIZATION_NAME;
note("Cloning '$CLONED_REPOSITORY' repository");

# clone repository
exec('git clone -- https://' . $hostPrefix . $pat . '@' . $HOST_REPOSITORY_ORGANIZATION_NAME . ' ' . $CLONE_DIR;

// debug
// ls -la "$CLONE_DIR"

note('Cleaning destination repository of old files');

# We're only interested in the .git directory, move it to $TARGET_DIR and use it from now on.
mkdir($TARGET_DIR . '/.git');
copy($CLONE_DIR . '/.git', $TARGET_DIR . '/.git');

//mkdir "$TARGET_DIR"
//mv "$CLONE_DIR/.git" "$TARGET_DIR/.git"

# cleanpu old unused data to avoid pushing them
rmdir($CLONE_DIR);
//rm -rf $CLONE_DIR

//ls -la "$TARGET_DIR"

note ("Copying contents to git repo of '$BRANCH' branch");

# copy the package directory including all hidden files to the clone dir
# make sure the source dir ends with `/.` so that all contents are copied (including .github etc)


copy($PACKAGE_DIRECTORY . '/.', $TARGET_DIR);
// cp -Ra $PACKAGE_DIRECTORY/. "$TARGET_DIR"

note ("Files that will be pushed");
//ls -la "$TARGET_DIR"

php src/commit_if_changed_files.php $TARGET_DIR $GITHUB_SHA $BRANCH




declare(strict_types=1);


// use like: php/commit_if_changed_files.php "<repository path to push>" "<commit sha-1>" "<branch>"
// $argv[0] is the file name itself
$repositoryPathToPush = $argv[1];
$currentCommitHash = $argv[2];
$branch = $argv[3];


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

// 1 = changed files
// 0 = no changed files
if ($hasChangedFiles === 1) {
    note('Adding git commit');

    $message = sprintf('Pushing git commit with "%s" message to "%s"', $commitMessage, $branch);
    note($message);

    exec("git commit --message '$commitMessage'");
    exec('git push --quiet origin ' . $branch);
} else {
    note('No files to change');
}


// restore original directory to avoid nesting WTFs
chdir($formerWorkingDirectory);


// push tag if present
if (getenv('TAG')) {
    $tag = getenv('TAG');
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
    echo PHP_EOL . "\033[0;33m[NOTE] " . $message . "\033[0m" . PHP_EOL . PHP_EOL;
}
