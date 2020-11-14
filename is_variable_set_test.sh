#!/bin/sh -l

# if a command fails it stops the execution
set -e

# script fails if trying to access to an undefined variable
set -u

TAG=1000

# push tag if present
if test ! -z "$TAG"
then
    echo "Tag is set to ${TAG}"
    echo $TAG
fi
