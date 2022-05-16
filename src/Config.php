<?php

// PHP 8.0

declare(strict_types=1);

namespace Symplify\MonorepoSplit;

final class Config
{
    public function __construct(
        private string $packageDirectory,
        private string $repositoryHost,
        private string $repositoryOrganization,
        private string $repositoryName,
        private string $commitHash,
        private string $branch,
        private ?string $tag,
        private ?string $userName,
        private ?string $userEmail,
        private string $accessToken
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
        // we need branches only for minor and major versions
        $versionParts = \explode('.', $this->branch);
        if (isset($versionParts[0]) && isset($versionParts[1]) && \count($versionParts) === 3) {
            return $versionParts[0] . '.' . $versionParts[1];
        }

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
