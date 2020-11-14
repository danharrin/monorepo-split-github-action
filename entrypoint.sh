#!/bin/sh -l

# if a command fails it stops the execution
set -e

# script fails if trying to access to an undefined variable
set -u

function note()
{
    MESSAGE=$1;

    printf "\n";
    echo "[NOTE] $MESSAGE";
    printf "\n";
}

note "Starts"

PACKAGE_DIRECTORY="$1"
SPLIT_REPOSITORY_ORGANIZATION="$2"
SPLIT_REPOSITORY_NAME="$3"
USER_EMAIL="$4"
COMMIT_MESSAGE="$5"
TAG="$6"

CLONE_DIR=$(mktemp -d)

CLONED_REPOSITORY="https://github.com/$SPLIT_REPOSITORY_ORGANIZATION/$SPLIT_REPOSITORY_NAME.git"
note "Cloning '$CLONED_REPOSITORY' repository "

# Setup git
git config --global user.email "$USER_EMAIL"
git config --global user.name "$SPLIT_REPOSITORY_ORGANIZATION"
git clone -- "https://$GITHUB_TOKEN@github.com/$SPLIT_REPOSITORY_ORGANIZATION/$SPLIT_REPOSITORY_NAME.git" "$CLONE_DIR"
ls -la "$CLONE_DIR"

note "Cleaning destination repository of old files"

# Copy files into the git and deletes all git
find "$CLONE_DIR" | grep -v "^$CLONE_DIR/\.git" | grep -v "^$CLONE_DIR$" | xargs rm -rf # delete all files (to handle deletions)
ls -la "$CLONE_DIR"

note "Copying contents to git repo"

cp -r "$PACKAGE_DIRECTORY"/* "$CLONE_DIR"
cd "$CLONE_DIR"
ls -la

note "Adding git commit"

ORIGIN_COMMIT="https://github.com/$GITHUB_REPOSITORY/commit/$GITHUB_SHA"
COMMIT_MESSAGE="${COMMIT_MESSAGE/ORIGIN_COMMIT/$ORIGIN_COMMIT}"

git add .
git status

# git diff-index : to avoid doing the git commit failing if there are no changes to be commit
git diff-index --quiet HEAD || git commit --message "$COMMIT_MESSAGE"

note "Pushing git commit"

# --set-upstream: sets the branch when pushing to a branch that does not exist
git push --quiet origin master

# push tag if present
if test ! -z "$TAG"
then
    note "Publishing tag: ${TAG}"

    git tag $TAG -m "Publishing tag ${TAG}"
    git push --quiet origin "${TAG}"
fi
