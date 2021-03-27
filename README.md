# GitHub Action for Monorepo Split

Do you have [a monorepo](https://tomasvotruba.com/cluster/monorepo-from-zero-to-hero/) project on GitHub and need split packages to many repositories? Add this GitHub Action to your workflow and let it split your packages on every commit and tag.

### How does the Split Result Look Like?

This repository splits tests into [symplify/monorepo-split-github-action-test](https://github.com/symplify/monorepo-split-github-action-test) repository.

Not on every commit, but only if contents of `/tests/packages/some-package` directory changes.
Try it yourself - send PR with change in [that directory](/tests/packages/some-package).

<br>

## Config

Split is basically git push or local directory to remote git repository. This remote repository can be located on GitHub or Gitlab. To be able to do that, it needs `GITHUB_TOKEN` or `GITLAB_TOKEN` with write repository access:

```yaml
env:
    GITLAB_TOKEN: ${{ secrets.GITLAB_TOKEN }}
```

Make sure to add this access token in "Secrets" of package settings: https://github.com/<organization>/<package>/settings/secrets/actions

<br>

## Split Packages Without and With Tag

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
