# How to use on GitLab?

GitHub Action is basically a yaml file that delegates parameters to a Docker run.

How? See [`action.yaml`](/action.yaml):

```yaml
runs:
    using: 'docker'
    image: 'Dockerfile'
    args:
        - ${{ inputs.package-directory }}
        - ${{ inputs.split-repository-organization }}
        - ${{ inputs.split-repository-name }}
        - ${{ inputs.branch }}
        - ${{ inputs.tag }}
        - ${{ inputs.user-email }}
        - ${{ inputs.user-name }}
        - ${{ inputs.split-repository-host }}
```

Saying that, we can use the same Docker to split our repository in GitLab:

## 1. Pull Image

```bash
docker pull symplify2/monorepo-split:latest
```

## 2. Use Docker Image

```bash
docker run symplify2/monorepo-split ... [args]
```

How to configure it with `gitlab-ci.yml`?

```yaml
stages:
    - split

split_monorepo:
    stage: split

    # see https://docs.gitlab.com/ee/ci/yaml/#parallel-matrix-jobs
    parallel:
        matrix:
            -
                LOCAL_PATH: "packages/easy-coding-standard"
                SPLIT_REPOSITORY: "easy-coding-standard"
            # + add more packages to split ...

    # see https://docs.gitlab.com/ee/ci/variables/#create-a-custom-cicd-variable-in-the-gitlab-ciyml-file
    variables:
        GITLAB_TOKEN: ${{ secrets.GITLAB_TOKEN }}
        PACKAGE_DIRETORY: $LOCAL_PATH
        SPLIT_REPOSITORY_ORGANIZATION: "symplify"
        SPLIT_REPOSITORY_NAME: $SPLIT_REPOSITORY
        BRANCH: "main"
        TAG: null
        USER_NAME: "kaizen-ci"
        USER_EMAIL: "info@kaizen-ci.org"
        SPLIT_REPOSITORY_HOST: "git.yourhost.com"

    script:
        - echo "Splitting $PACKAGE_DIRETORY to $SPLIT_REPOSITORY_NAME"
        - docker pull symplify2/monorepo-split:latest
        - docker run symplify2/monorepo-split $PACKAGE_DIRECTORY $SPLIT_REPOSITORY_ORGANIZATION $SPLIT_REPOSITORY_NAME $BRANCH $TAG $USER_NAME $USER_EMAIL $SPLIT_REPOSITORY_HOST
```
