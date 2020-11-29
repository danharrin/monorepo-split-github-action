# GitHub Action for Monorepo Split

Based heavily on [cpina/github-action-push-to-another-repository](https://github.com/cpina/github-action-push-to-another-repository), with focus on automated monorepo splits.

## Example


### Split Packages With Tag

```yaml
name: 'Monorepo Split With Tag'

on:
    push:
        branches:
            - master

jobs:
    monorepo_split_test_with_tag:
        runs-on: ubuntu-latest

        steps:
            -
                uses: actions/checkout@v2
                # this is required for "WyriHaximus/github-action-get-previous-tag" workflow
                # see https://github.com/actions/checkout#fetch-all-history-for-all-tags-and-branches
                with:
                    fetch-depth: 0

            # see https://github.com/WyriHaximus/github-action-get-previous-tag
            -
                id: previous_tag
                uses: "WyriHaximus/github-action-get-previous-tag@master"

            -
                # Uses an action in the root directory
                uses: "symplify/monorepo-split-github-action@master"
                env:
                    GITHUB_TOKEN: ${{ secrets.ACCESS_TOKEN }}
                with:
                    package-directory: 'packages/easy-coding-standard'
                    split-repository-organization: 'symplify'
                    split-repository-name: 'easy-coding-standard'
                    tag: ${{ steps.previous_tag.outputs.tag }}
                    # change to use that should be signed under the split commit 
                    user-name: "kaizen-ci"
                    user-email: "info@kaizen-ci.org"
```
