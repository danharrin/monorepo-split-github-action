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
        private string $currentCommitHash,
        private ?string $currentBranch,
        private ?string $currentTag,
        private ?string $gitUserName,
        private ?string $gitUserEmail,
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

    public function getCurrentBranch(): ?string
    {
        return $this->currentBranch;
    }

    public function getCurrentTag(): ?string
    {
        return $this->currentTag;
    }

    public function getCurrentCommitHash(): ?string
    {
        return $this->currentCommitHash;
    }
}
