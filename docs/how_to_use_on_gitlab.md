# How to use on GitLab?

GitHub Action is basically a yaml file that delegates parameters to a Docker run.
We can use the same Docker image to split our repository in GitLab.

## Configure `gitlab-ci.yml`

You will need to [create personal access token](https://gitlab.com/-/profile/personal_access_tokens) and add the token to your Gitlab environment variable named `GITLAB_TOKEN`.

All we need to do is configure input arguments:

```yaml
stages:
    - split

split_monorepo:
    stage: split
    image:
        name: symplify2/monorepo-split:latest
        entrypoint: ["/usr/bin/env"]
    # see https://docs.gitlab.com/ee/ci/yaml/#parallel-matrix-jobs
    parallel:
        matrix:
            # here must be at least 2 items
            -
                LOCAL_PATH: "packages/easy-coding-standard"
                SPLIT_REPOSITORY: "easy-coding-standard"
            -
                LOCAL_PATH: "packages/coding-standard"
                SPLIT_REPOSITORY: "coding-standard"
            # + add more packages to split ...

    # see https://docs.gitlab.com/ee/ci/variables/#create-a-custom-cicd-variable-in-the-gitlab-ciyml-file
    variables:
        GITLAB_TOKEN: ${{ secrets.GITLAB_TOKEN }}
        PACKAGE_DIRECTORY: $LOCAL_PATH
        REPOSITORY_ORGANIZATION: "symplify"
        REPOSITORY_NAME: $SPLIT_REPOSITORY
        BRANCH: "main"
        TAG: ""
        USER_NAME: "kaizen-ci"
        USER_EMAIL: "info@kaizen-ci.org"
        REPOSITORY_HOST: "git.yourhost.com"
    script:
        - echo "Splitting $PACKAGE_DIRECTORY to $SPLIT_REPOSITORY_NAME"
        - php /splitter/entrypoint.php
    when: always
```
