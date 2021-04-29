<?php

declare(strict_types=1);

namespace Symplify\MonorepoSplit;

use Symplify\MonorepoSplit\Exception\ConfigurationException;

final class ConfigFactory
{
    /**
     * @var string
     */
    private const GITLAB = 'GITLAB';

    /**
     * @var string
     */
    private const GITHUB = 'GITHUB';

    /**
     * @var string
     */
    private const DEFAULT_BRANCH = 'main';

    /**
     * @todo verify
     * @var string
     */
    private const DEFAULT_GITLAB_HOST = 'gitlab.com';

    private PublicAccessTokenResolver $publicAccessTokenResolver;

    public function __construct()
    {
        $this->publicAccessTokenResolver = new PublicAccessTokenResolver();
    }

    /**
     * @param array<string, mixed> $env
     */
    public function create(array $env): Config
    {
        $ciPlatform = $this->resolvePlatform($env);
        $accessToken = $this->publicAccessTokenResolver->resolve($env);
        $commitSha = $this->resolveCommitSha($ciPlatform, $env);

        return $this->createFromEnv($env, $accessToken, $commitSha, $ciPlatform);
    }

    /**
     * @param array<string, mixed> $env
     */
    private function resolvePlatform(array $env): string
    {
        return isset($env['GITLAB_CI']) ? self::GITLAB : self::GITHUB;
    }

    /**
     * @param array<string, mixed> $env
     */
    private function createFromEnv(array $env, string $accessToken, string $commitSha, string $ciPlatform): Config
    {
        $envPrefix = $ciPlatform === self::GITHUB ? 'INPUT_' : '';

        return new Config(
            localDirectory: $env[$envPrefix . 'PACKAGE_DIRECTORY'] ?? throw new ConfigurationException('Package directory is missing'),
            splitRepositoryHost: $env[$envPrefix . 'SPLIT_REPOSITORY_HOST'] ?? throw new ConfigurationException('Repository host is missing'),
            splitRepositoryOrganiation: $env[$envPrefix . 'SPLIT_REPOSITORY_ORGANIZATION'] ?? throw new ConfigurationException(
                'Repository organization is missing'
            ),
            splitRepositoryName: $env[$envPrefix . 'SPLIT_REPOSITORY_NAME'] ?? throw new ConfigurationException('Repository name is missing'),
            // optional
            branch: $env[$envPrefix . 'BRANCH'] ?? null,
            tag: $env[$envPrefix . 'TAG'] ?? null,
            gitUserName: $env[$envPrefix . 'USER_EMAIL'] ?? null,
            gitUserEmail: $env[$envPrefix . 'USER_NAME'] ?? null,
            // required
            commitHash: $commitSha,
            accessToken: $accessToken
        );
    }

    /**
     * @param array<string, mixed> $env
     */
    private function resolveCommitSha(string $ciPlatform, array $env): string
    {
        return $ciPlatform === self::GITLAB ? $env['CI_COMMIT_SHA'] : $env['GITHUB_SHA'];
    }
}
