<?php

declare(strict_types=1);

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

        throw new RuntimeException('Public access token is missing, add it via: "PAT", "GITHUB_TOKEN" or "GITLAB_TOKEN"');
    }
}
