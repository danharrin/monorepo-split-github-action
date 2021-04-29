<?php

declare(strict_types=1);

namespace Symplify\MonorepoSplit;

use Symplify\MonorepoSplit\Exception\ConfigurationException;

final class PublicAccessTokenResolver
{
    /**
     * @var string
     */
    private const PAT = 'PAT';

    /**
     * @var string
     */
    private const GITHUB_TOKEN = 'GITHUB_TOKEN';

    /**
     * @var string
     */
    private const GITLAB_TOKEN = 'GITLAB_TOKEN';

    /**
     * @var string[]
     */
    private const POSSIBLE_TOKEN_ENVS = [
        self::PAT,
        self::GITLAB_TOKEN,
        self::GITHUB_TOKEN,
    ];

    /**
     * @param array<string, mixed> $env
     */
    public function resolve(array $env): string
    {
        if (isset($env[self::PAT])) {
            return $env[self::PAT];
        }

        if (isset($env[self::GITHUB_TOKEN])) {
            return $env[self::GITHUB_TOKEN];
        }

        if (isset($env[self::GITLAB_TOKEN])) {
            return 'oauth2:' . $env[self::GITLAB_TOKEN];
        }

        $message = sprintf(
            'Public access token is missing, add it via: "%s"',
            implode('", "', self::POSSIBLE_TOKEN_ENVS)
        );

        throw new ConfigurationException($message);
    }
}
