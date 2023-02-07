<?php

declare(strict_types=1);

namespace PhpProject\TestSuite\Tests\Resolver\PhpUnit;

use PhpProject\SourceCode\Classes\Builder\ClassManagerBuilder;
use PhpProject\SourceCode\Classes\Manager\ClassManager;
use PhpProject\SourceCode\Files\Manager\FileManager;
use PhpProject\SourceCode\Files\Path\AbsolutePath;
use PhpProject\SourceCode\Files\Path\RelativePath;
use PhpProject\SourceCode\Files\SourceFile;
use PhpProject\TestSuite\Builder\TestSuiteConfigurationResolverBuilder;
use PhpProject\TestSuite\Configuration\TestSuiteConfig;
use PhpProject\TestSuite\Configuration\TestSuitesConfig;
use PhpProject\TestSuite\Resolver\PhpUnit\PhpUnitTestSuiteConfigurationResolver;
use PhpProject\TestSuite\Resolver\TestSuiteConfigurationResolver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('tests')]
#[Group('phpunit')] final class PhpUnitTestSuiteConfigurationResolverTest extends TestCase
{
    private const BASE_PATH = __DIR__.'/../../../data/fake-project';
    private TestSuiteConfigurationResolver $testSuiteConfigResolver;
    private ClassManager $classManager;

    protected function setUp(): void
    {
        $projectPath        = AbsolutePath::raw((string) realpath(self::BASE_PATH));
        $autoloadPath       = RelativePath::raw('../../../vendor/autoload.php');
        $this->classManager = ClassManagerBuilder::for(FileManager::build($projectPath))->usingComposer($autoloadPath)->build();
    }

    /**
     * @throws \Exception
     */
    #[Test]
    public function it_returns_the_test_files(): void
    {
        $_this = $this; // #ignoreLine

        $_this->given_a_fake_test_project();
        $_suites = $_this->when_I_resolve_the_phpunit_configuration_file();
        $_suite  = $_this->there_should_be_one_test_suite($_suites);
        $_this->the_suite_should_be_named('fake', $_suite);
        $_this->all_the_files_should_have_been_found($_suite);
        $_this->the_filter_should_match_Test_classes_and_test_prefixed_or_annotated_methods($_suite);
    }

    // 1. Arrange

    private function given_a_fake_test_project(): void
    {
        $fileManager                   = FileManager::build(AbsolutePath::clean((string) realpath(self::BASE_PATH)));
        $this->testSuiteConfigResolver = TestSuiteConfigurationResolverBuilder::for($fileManager)
            ->usingPhpUnit()
            ->build();
        self::assertInstanceOf(PhpUnitTestSuiteConfigurationResolver::class, $this->testSuiteConfigResolver);
    }

    // 2. Act

    private function when_I_resolve_the_phpunit_configuration_file(): TestSuitesConfig
    {
        return $this->testSuiteConfigResolver->getTestSuites();
    }

    // 3. Assert

    public function there_should_be_one_test_suite(TestSuitesConfig $suites): TestSuiteConfig
    {
        self::assertCount(1, $suites);
        $onlySuite = null;
        foreach ($suites as $suite) {
            $onlySuite = $suite;
        }
        $arrayAccessSuite = $suites->asArray()[0];
        self::assertEquals($arrayAccessSuite, $onlySuite);

        return $arrayAccessSuite;
    }

    private function the_suite_should_be_named(string $string, TestSuiteConfig $suite): void
    {
        self::assertEquals($string, $suite->name);
    }

    private function all_the_files_should_have_been_found(TestSuiteConfig $suite): void
    {
        $files = $suite->source->asArray();
        self::assertCount(5, $files);

        $paths = array_map(
            static fn (SourceFile $file): string => (string) $file->path,
            $files
        );

        self::assertContains('tests/All/AllMatchingTest.php', $paths);
        self::assertNotContains('tests/All/Random.php', $paths);
        self::assertNotContains('tests/All/Exclude/ExcludeTest.php', $paths);

        self::assertContains('tests/Alone/AloneTest.php', $paths);

        self::assertContains('tests/Random/NotATest.php', $paths);

        if (\PHP_VERSION_ID >= 80000) {
            self::assertContains('tests/Alone/AloneMorePhp800gtTest.php', $paths);
            self::assertNotContains('tests/Alone/AloneLessPhp800ltTest.php', $paths);

            self::assertContains('tests/MorePhp800gt/MatchingTest.php', $paths);
            self::assertNotContains('tests/MorePhp800gt/NotMatchingTest.php', $paths);

            self::assertNotContains('tests/LessPhp800lt/MatchingTest.php', $paths);
            self::assertNotContains('tests/LessPhp800lt/NotMatchingTest.php', $paths);
        } else {
            self::assertContains('tests/Alone/AloneLessPhp800ltTest.php', $paths);
            self::assertNotContains('tests/Alone/AloneMorePhp800gtTest.php', $paths);

            self::assertContains('tests/LessPhp800lt/MatchingTest.php', $paths);
            self::assertNotContains('tests/LessPhp800lt/NotMatchingTest.php', $paths);

            self::assertNotContains('tests/MorePhp800gt/MatchingTest.php', $paths);
            self::assertNotContains('tests/MorePhp800gt/NotMatchingTest.php', $paths);
        }
    }

    /**
     * @throws \Exception
     */
    private function the_filter_should_match_Test_classes_and_test_prefixed_or_annotated_methods(TestSuiteConfig $suite): void
    {
        $classes = $this->classManager->getClasses($suite->source, $suite->filter);
        self::assertCount(4, $classes);

        $expectedClassesNames = [
            'AllMatchingTest',
            'AloneLessPhp800ltTest',
            'AloneMorePhp800gtTest',
            'AloneTest',
            'MatchingTest',
        ];

        foreach ($classes->getIterator() as $class) {
            self::assertContains($class->shortName(), $expectedClassesNames);

            if ($class->shortName() === 'AllMatchingTest') {
                self::assertCount(2, $class->methods);
            } else {
                self::assertCount(0, $class->methods);
            }
        }
    }
}
