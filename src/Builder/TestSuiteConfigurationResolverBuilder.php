<?php

declare(strict_types=1);

namespace PhpProject\TestSuite\Builder;

use Assert\Assert;
use PhpProject\SourceCode\Files\Manager\FileManager;
use PhpProject\SourceCode\Files\Path\RelativePath;
use PhpProject\TestSuite\Resolver\PhpUnit\PhpUnitConfigurationLoader;
use PhpProject\TestSuite\Resolver\PhpUnit\PhpUnitTestSuiteConfigurationResolver;
use PhpProject\TestSuite\Resolver\TestSuiteConfigurationResolver;

final class TestSuiteConfigurationResolverBuilder
{
    private ?TestSuiteConfigurationResolver $resolver = null;

    private function __construct(
        private readonly FileManager $projectFileManager
    ) {
    }

    public static function for(FileManager $projectFileManager): self
    {
        return new self($projectFileManager);
    }

    public function usingPhpUnit(?RelativePath $configFile = null): self
    {
        $this->resolver = new PhpUnitTestSuiteConfigurationResolver(
            new PhpUnitConfigurationLoader(),
            $this->projectFileManager,
            $configFile
        );

        return $this;
    }

    public function build(): TestSuiteConfigurationResolver
    {
        Assert::lazy()
            ->that($this->resolver)->notNull('You have to configure a test suite configuration resolver.')
            ->verifyNow();

        return $this->resolver; // @phpstan-ignore-line
    }
}
