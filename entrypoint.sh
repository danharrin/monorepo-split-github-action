#!/bin/sh -l

# if a command fails it stops the execution
set -e

# script fails if trying to access to an undefined variable
set -u

echo "Starts"
PACKAGE_DIRECTORY="$1"
DESTINATION_GITHUB_USERNAME="$2"
DESTINATION_REPOSITORY_NAME="$3"
USER_EMAIL="$4"
COMMIT_MESSAGE="$5"

CLONE_DIR=$(mktemp -d)

echo "Cloning destination git repository"

# Setup git
git config --global user.email "$USER_EMAIL"
git config --global user.name "$DESTINATION_GITHUB_USERNAME"
git clone -- "https://$API_TOKEN_GITHUB@github.com/$DESTINATION_GITHUB_USERNAME/$DESTINATION_REPOSITORY_NAME.git" "$CLONE_DIR"
ls -la "$CLONE_DIR"

echo "Cleaning destination repository of old files"
# Copy files into the git and deletes all git
find "$CLONE_DIR" | grep -v "^$CLONE_DIR/\.git" | grep -v "^$CLONE_DIR$" | xargs rm -rf # delete all files (to handle deletions)
ls -la "$CLONE_DIR"

echo "Copying contents to git repo"
cp -r "$PACKAGE_DIRECTORY"/* "$CLONE_DIR"
cd "$CLONE_DIR"
ls -la

echo "Adding git commit"
ORIGIN_COMMIT="https://github.com/$GITHUB_REPOSITORY/commit/$GITHUB_SHA"
COMMIT_MESSAGE="${COMMIT_MESSAGE/ORIGIN_COMMIT/$ORIGIN_COMMIT}"

git add .
git status

# git diff-index : to avoid doing the git commit failing if there are no changes to be commit
git diff-index --quiet HEAD || git commit --message "$COMMIT_MESSAGE"

echo "Pushing git commit"

# --set-upstream: sets de branch when pushing to a branch that does not exist
git push origin --set-upstream "$TARGET_BRANCH"

# push tag if present
