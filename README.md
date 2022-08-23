# GitHub Action for Monorepo Split

**Version 2.0 now supports split to GitHub and Gitlab private repositories!**

<br>

Do you have [a monorepo](https://tomasvotruba.com/cluster/monorepo-from-zero-to-hero/) project on GitHub and need split packages to many repositories? Add this GitHub Action to your workflow and let it split your packages on every commit and tag.

### How does the Split Result Look Like?

This repository splits tests into [symplify/monorepo-split-github-action-test](https://github.com/symplify/monorepo-split-github-action-test) repository.

Not on every commit, but only if contents of `/tests/packages/some-package` directory changes.
Try it yourself - send PR with change in [that directory](/tests/packages/some-package).

<br>

## Docs

- [How to Use on Gitlab](docs/how_to_use_on_gitlab.md)

## Config

Split is basically git push or local directory to remote git repository. This remote repository can be located on GitHub or Gitlab. To be able to do that, it needs `GITHUB_TOKEN` or `GITLAB_TOKEN` with write repository access:

```yaml
env:
    GITHUB_TOKEN: ${{ secrets.ACCESS_TOKEN }}
    # or
    GITLAB_TOKEN: ${{ secrets.GITLAB_TOKEN }}
```

Make sure to add this access token in "Secrets" of package settings: `https://github.com/<organization>/<package>/settings/secrets/actions`

<br>

## Define your GitHub Workflow

```yaml
name: 'Packages Split'

on:
    push:
        branches:
            - main
        tags:
            - '*'

env:
    # 1. for Github split
    GITHUB_TOKEN: ${{ secrets.ACCESS_TOKEN }}

    # 2. for Gitlab split
    GITLAB_TOKEN: ${{ secrets.GITLAB_TOKEN }}

jobs:
    packages_split:
        runs-on: ubuntu-latest

        strategy:
            fail-fast: false
            matrix:
                # define package to repository map
                package:
                    -
                        local_path: 'easy-coding-standard'
                        split_repository: 'easy-coding-standard'

        steps:
            -   uses: actions/checkout@v2

            # no tag
            -
                if: "${{ github.ref_type != 'tag' }}"
                uses: "symplify/monorepo-split-github-action@2.1"
                with:
                    # ↓ split "packages/easy-coding-standard" directory
                    package_directory: 'packages/${{ matrix.package.local_path }}'

                    # ↓ into https://github.com/symplify/easy-coding-standard repository
                    repository_organization: 'symplify'
                    repository_name: '${{ matrix.package.split_repository }}'

                    # [optional, with "github.com" as default]
                    repository_host: git.private.com:1234

                    # ↓ the user signed under the split commit
                    user_name: "kaizen-ci"
                    user_email: "info@kaizen-ci.org"

            # with tag
            -
                if: "${{ github.ref_type == 'tag' }}"
                uses: "symplify/monorepo-split-github-action@2.1"
                with:
                    tag: ${GITHUB_REF#refs/tags/}

                    # ↓ split "packages/easy-coding-standard" directory
                    package_directory: 'packages/${{ matrix.package.local_path }}'

                    # ↓ into https://github.com/symplify/easy-coding-standard repository
                    repository_organization: 'symplify'
                    repository_name: '${{ matrix.package.split_repository }}'

                    # [optional, with "github.com" as default]
                    repository_host: git.private.com:1234

                    # ↓ the user signed under the split commit
                    user_name: "kaizen-ci"
                    user_email: "info@kaizen-ci.org"
```
