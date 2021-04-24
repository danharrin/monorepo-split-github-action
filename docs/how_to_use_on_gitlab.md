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

```

```
