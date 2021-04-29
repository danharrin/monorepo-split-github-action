<?php

declare(strict_types=1);

namespace Symplify\MonorepoSplit;

use RuntimeException;

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

    public function resolve(): string
    {
        if (getenv(self::PAT)) {
            return getenv(self::PAT);
        }

        if (getenv(self::GITHUB_TOKEN)) {
            return getenv(self::GITHUB_TOKEN);
        }

        if (getenv(self::GITLAB_TOKEN)) {
            return 'oauth2:' . getenv(self::GITLAB_TOKEN);
        }

        $message = sprintf(
            'Public access token is missing, add it via: "%s"', implode('", "',
                self::POSSIBLE_TOKEN_ENVS)
        );

        throw new RuntimeException($message);
    }
}
