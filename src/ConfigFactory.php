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
        $commitHash = $this->resolveCommitHash($ciPlatform, $env);

        return $this->createFromEnv($env, $accessToken, $commitHash, $ciPlatform);
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
    private function createFromEnv(array $env, string $accessToken, string $commitHash, string $ciPlatform): Config
    {
        $envPrefix = $ciPlatform === self::GITHUB ? 'INPUT_' : '';

        return new Config(
            packageDirectory: $env[$envPrefix . 'PACKAGE_DIRECTORY'] ?? throw new ConfigurationException('Package directory is missing'),
            repositoryHost: $env[$envPrefix . 'REPOSITORY_HOST'] ?? throw new ConfigurationException('Repository host is missing'),
            repositoryOrganization: $env[$envPrefix . 'REPOSITORY_ORGANIZATION'] ?? throw new ConfigurationException(
                'Repository organization is missing'
            ),
            repositoryName: $env[$envPrefix . 'REPOSITORY_NAME'] ?? throw new ConfigurationException('Repository name is missing'),
            // optional
            branch: $env[$envPrefix . 'BRANCH'] ?? null,
            tag: $env[$envPrefix . 'TAG'] ?? null,
            userName: $env[$envPrefix . 'USER_EMAIL'] ?? null,
            userEmail: $env[$envPrefix . 'USER_NAME'] ?? null,
            // required
            commitHash: $commitHash,
            accessToken: $accessToken
        );
    }

    /**
     * @param array<string, mixed> $env
     */
    private function resolveCommitHash(string $ciPlatform, array $env): string
    {
        return $ciPlatform === self::GITLAB ? $env['CI_COMMIT_SHA'] : $env['GITHUB_SHA'];
    }
}
