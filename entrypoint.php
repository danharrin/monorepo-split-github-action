<?php

declare(strict_types=1);

use Symplify\MonorepoSplit\Config;
use Symplify\MonorepoSplit\ConfigFactory;
use Symplify\MonorepoSplit\Exception\ConfigurationException;
use Symplify\MonorepoSplit\Github;
use Symplify\MonorepoSplit\GithubException;


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


$cloneDirectory = sys_get_temp_dir() . '/monorepo_split/clone_directory';
$buildDirectory = sys_get_temp_dir() . '/monorepo_split/build_directory';

$hostRepositoryOrganizationName = $config->getGitRepository();

// info
$clonedRepository='https://' . $hostRepositoryOrganizationName;
$cloningMessage = sprintf('Cloning "%s" repository to "%s" directory', $clonedRepository, $cloneDirectory);
note($cloningMessage);

$commandLine = 'git clone -- https://' . $config->getAccessToken() . '@' . $hostRepositoryOrganizationName . ' ' . $cloneDirectory;
exec_with_note($commandLine);

note("Checking existence of " . $cloneDirectory . "/.git");

if ( !is_dir($cloneDirectory . "/.git")) {
    error($cloneDirectory . "/.git does not exist"); 
    // should we exit 1 here? for backwards compatibility lets leave this just here.
    
    // entering new behaviours.
    if ( $config->getAutoCreateRepo() ) { 

        // Github specific behaviour
        if ( strpos($hostRepositoryOrganizationName,"github.com") !== false ) {
            
            note("Automatically creating repo on github");


            /*
             * Do some github specific actions to create the repository within the github user or organization account.
             * 
             */
            Github::useAuth($config->getUsername(), $config->getAccessToken());

            // extract the working repository url;
            exec('git remote get-url --push origin | head -n 1', $remote);
            $origremote = trim(implode(PHP_EOL, $remote));

            if ( strpos($origremote, "git@") !== false) {
                // current repo uses SSH, reformat that to http url for easy extaction.
                $origremote = str_replace("git@","https://",$origremote);
                $origremote = str_replace(".com:",".com/", $origremote);
            }

            // get the username/path part from main upstream repo which wil be split.
            $remotepath = ltrim(parse_url($origremote, PHP_URL_PATH),"/");
            $remotepath = str_replace(".git","",$remotepath);
            
            list($parentlogin, $parentrepo) = explode("/",$remotepath);

            // gets the parent repository from github.
            $parentinfo = Github::getRepo($remotepath);
            $private = $parentinfo->private;

            
            $childlogin = $config->getOrganization();
            $childrepo = $config->getPackageName();

            try {
                $entity = Github::getUser($childlogin);
            } catch(\GithubException $e ) {
                $entity = Github::getOrganization($childlogin);
            }

            if ($entity && $entity->type == "User" ) {
                $offspringurl = "/user/repos";
            }

            if ($entity && $entity->type == "Organization" ) {
                $offspringurl = "/orgs/{$childlogin}/repos";
            }

            note("Splitting $parentlogin/$parentrepo into $childlogin/$childrepo ( post target: $offspringurl )");
            $payload = [
                'name' => $childrepo,
                'description' => '[READ ONLY] ' . $childlogin . '/' . $childrepo . ' package ( splitted from ' . $parentlogin . '/' . $parentrepo . ' )',
                'private' => $private,
                'homepage' => 'https://github.com/' . $parentlogin . '/' . $parentrepo,
            ];            

            try {
                Github::createRepo(
                    path: $offspringurl,
                    payload: $payload
                );
            } catch ( GithubException $e ) {
                // coudn't create repo, so dont continue;
                error($e->getMessage());
                exit(1);
            }

            /*
             * Initiate a new git repository to build to
             */

            $formerWorkingDirectory = getcwd();
            mkdir($cloneDirectory);
            chdir($cloneDirectory);

            exec_with_output_print('echo "# ' . $childlogin . "/" . $childrepo . '" >> README.md');
            exec_with_output_print('git init --initial-branch=main');
            exec_with_output_print('git add README.md');
            exec_with_output_print('git commit -m "first commit"');
            exec_with_output_print('git remote add origin https://' . $config->getAccessToken() . '@' . $hostRepositoryOrganizationName );
            exec_with_output_print('git push -u origin main');
            
            // make sure we are working in the right branch
            exec_with_output_print('git checkout -b '.$config->getBranch());
            
            chdir($formerWorkingDirectory);
            


        } else {
            note("Current host is currently not supported for auto creating repositories");
        }

    } else {
        note("Automatically creating repo was not enabled");
    }

} 



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

    exec("git commit --message '$commitMessage'");
    exec('git push --quiet origin ' . $config->getBranch());
} else {
    note('No files to change');
}


// push tag if present
if ($config->getTag()) {
    $message = sprintf('Publishing "%s"', $config->getTag());
    note($message);

    $commandLine = sprintf('git tag %s -m "%s"', $config->getTag(), $message);
    exec_with_note($commandLine);

    exec_with_note('git push --quiet origin ' . $config->getTag());
    
}


// restore original directory to avoid nesting WTFs
chdir($formerWorkingDirectory);
$chdirMessage = sprintf('Changing directory from "%s" to "%s"', $buildDirectory, $formerWorkingDirectory);
note($chdirMessage);


function createCommitMessage(string $commitSha): string
{
    exec("git show -s --format=%B $commitSha", $outputLines);
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




function list_directory_files(string $directory): void {
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
    echo implode(PHP_EOL, $outputLines).PHP_EOL;
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
