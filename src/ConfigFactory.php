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
     * @param array<int, mixed> $argv
     * @param array<string, mixed> $env
     */
    public function create(array $argv, array $env): Config
    {
        $ciPlatform = $this->resolvePlatform($env);
        $accessToken = $this->publicAccessTokenResolver->resolve($env);

        if ($ciPlatform === self::GITHUB) {
            return $this->createForGitHub($argv, $env, $accessToken);
        }

        return $this->createForGitlab($env, $accessToken);
    }

    /**
     * @param array<string, mixed> $env
     */
    private function resolvePlatform(array $env): string
    {
        // 1. using GitHub
        if (isset($env['GITLAB_CI'])) {
            return self::GITLAB;
        }

        return self::GITHUB;
    }

    /**
     * @param array<int, mixed> $argv
     * @param array<string, mixed> $env
     */
    private function createForGitHub(array $argv, array $env, string $accessToken): Config
    {
        return new Config(
            localDirectory: $argv[1] ?? throw new ConfigurationException('Package directory is missing'),
            splitRepositoryHost: $argv[8] ?? throw new ConfigurationException('Repository host is missing'),
            splitRepositoryOrganiation: $argv[2] ?? throw new ConfigurationException(
                'Repository organization is missing'
            ),
            splitRepositoryName: $argv[3] ?? throw new ConfigurationException('Repository name is missing'),
            currentBranch: $argv[4] ?? null,
            currentTag: $argv[5] ?? null,
            gitUserName: $argv[7] ?? null,
            gitUserEmail: $argv[6] ?? null,
            currentCommitHash: $env['GITHUB_SHA'] ?? throw new ConfigurationException('Commit hash is missing'),
            accessToken: $accessToken
        );
    }

    /**
     * @param array<string, mixed> $env
     */
    private function createForGitlab(array $env, string $accessToken): Config
    {
        return new Config(
            localDirectory: $env['PACKAGE_DIRECTORY'],
            splitRepositoryHost: $env['SPLIT_REPOSITORY_HOST'] ?? self::DEFAULT_GITLAB_HOST,
            splitRepositoryOrganiation: $env['SPLIT_REPOSITORY_ORGANIZATION'],
            splitRepositoryName: $env['SPLIT_REPOSITORY'],
            // @see https://docs.gitlab.com/ee/ci/variables/#enable-debug-logging
            currentCommitHash: $env['CI_COMMIT_SHA'],
            currentBranch: $env['BRANCH'] ?? self::DEFAULT_BRANCH,
            gitUserName: $env['USER_NAME'] ?? null,
            gitUserEmail: $env['USER_EMAIL'] ?? null,
            currentTag: $env['TAG'] ?? null,
            accessToken: $accessToken
        );
    }
}
