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
        private bool $autoCreateRepo,
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

    public function getAutoCreateRepo(): bool
    {
        return $this->autoCreateRepo;
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
    public function getPackageName(): string 
    {
        return $this->repositoryName;
    }
    public function getOrganization(): string 
    {
        return $this->repositoryOrganization;
    }
}
