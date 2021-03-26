# GitHub Action for Monorepo Split

Based heavily on [cpina/github-action-push-to-another-repository](https://github.com/cpina/github-action-push-to-another-repository), with focus on automated monorepo splits.

How does the result look like? This repository splits tests into [symplify/monorepo-split-github-action-test](https://github.com/symplify/monorepo-split-github-action-test) repository.

## Example


### Split Packages Without and With Tag

```yaml
name: 'Packages Split'

on:
    push:
        branches:
            - main
        tags:
            - '*'

jobs:
    packages_split:
        runs-on: ubuntu-latest

        steps:
            -
                uses: actions/checkout@v2
                # this is required for "WyriHaximus/github-action-get-previous-tag" workflow
                # see https://github.com/actions/checkout#fetch-all-history-for-all-tags-and-branches
                with:
                    fetch-depth: 0

            # no tag
            -
                if: "!startsWith(github.ref, 'refs/tags/')"
                uses: "symplify/monorepo-split-github-action@1.1"
                with:
                    # ↓ split "packages/easy-coding-standard" directory
                    package-directory: 'packages/easy-coding-standard'

                    # ↓ into https://github.com/symplify/easy-coding-standard repository
                    split-repository-organization: 'symplify'
                    split-repository-name: 'easy-coding-standard'

                    # ↓ the user signed under the split commit
                    user-name: "kaizen-ci"
                    user-email: "info@kaizen-ci.org"

            # with tag
            -
                if: "!startsWith(github.ref, 'refs/tags/')"
                uses: "symplify/monorepo-split-github-action@1.1"
                with:
                    tag: ${GITHUB_REF#refs/tags/}

                    # ↓ split "packages/easy-coding-standard" directory
                    package-directory: 'packages/easy-coding-standard'

                    # ↓ into https://github.com/symplify/easy-coding-standard repository
                    split-repository-organization: 'symplify'
                    split-repository-name: 'easy-coding-standard'

                    # ↓ the user signed under the split commit
                    user-name: "kaizen-ci"
                    user-email: "info@kaizen-ci.org"
```
