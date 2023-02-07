<?php

declare(strict_types=1);

namespace PhpProject\TestSuite\Tests\Builder;

use PhpProject\SourceCode\Files\Manager\FileManager;
use PhpProject\SourceCode\Files\Path\AbsolutePath;
use PhpProject\TestSuite\Builder\TestSuiteConfigurationResolverBuilder;
use PhpProject\TestSuite\Resolver\PhpUnit\PhpUnitTestSuiteConfigurationResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TestSuiteConfigurationResolverBuilderTest extends TestCase
{
    private TestSuiteConfigurationResolverBuilder $builder;

    protected function setUp(): void
    {
        $fileManager   = FileManager::build(AbsolutePath::clean(__DIR__.'/../../..'));
        $this->builder = TestSuiteConfigurationResolverBuilder::for($fileManager);
    }

    #[Test]
    public function it_builds_a_test_suite_config_resolver_for_php_unit(): void
    {
        $resolver = $this->builder
            ->usingPhpUnit()
            ->build();

        self::assertInstanceOf(PhpUnitTestSuiteConfigurationResolver::class, $resolver);
    }

    #[Test]
    public function it_cannot_build_a_test_suite_config_resolver_if_not_given_a_test_framework(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->builder->build();
    }
}
