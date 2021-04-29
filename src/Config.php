<?php

// PHP 8.0

declare(strict_types=1);

namespace Symplify\MonorepoSplit;

final class Config
{
    public function __construct(
        private string $localDirectory,
        private string $splitRepositoryHost,
        private string $splitRepositoryOrganiation,
        private string $splitRepositoryName,
        private string $commitHash,
        private string $branch,
        private ?string $tag,
        private ?string $gitUserName,
        private ?string $gitUserEmail,
        private string $accessToken
    ) {
    }

    public function getLocalDirectory(): string
    {
        return $this->localDirectory;
    }

    public function getSplitRepositoryHost(): string
    {
        return $this->splitRepositoryHost;
    }

    public function getSplitRepositoryOrganiation(): string
    {
        return $this->splitRepositoryOrganiation;
    }

    public function getSplitRepositoryName(): string
    {
        return $this->splitRepositoryName;
    }

    public function getGitUserName(): ?string
    {
        return $this->gitUserName;
    }

    public function getGitUserEmail(): ?string
    {
        return $this->gitUserEmail;
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
        return $this->splitRepositoryHost . '/' . $this->splitRepositoryOrganiation . '/' . $this->splitRepositoryName . '.git';
    }
}
