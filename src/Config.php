<?php

// PHP 8.0

declare(strict_types=1);

namespace Symplify\MonorepoSplit;

final class Config
{
    public function __construct(
        private readonly string $packageDirectory,
        private readonly string $repositoryHost,
        private readonly string $repositoryOrganization,
        private readonly string $repositoryName,
        private readonly string $commitHash,
        private readonly string $branch,
        private readonly ?string $tag,
        private readonly ?string $userName,
        private readonly ?string $userEmail,
        private readonly string $accessToken
    ) {
    }

    public function getPackageDirectory(): string
    {
        return $this->packageDirectory;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function getBranch(): ?string
    {
        return $this->branch;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function getCommitHash(): string
    {
        return $this->commitHash;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function getGitRepository(): string
    {
        return $this->repositoryHost . '/' . $this->repositoryOrganization . '/' . $this->repositoryName . '.git';
    }
}
