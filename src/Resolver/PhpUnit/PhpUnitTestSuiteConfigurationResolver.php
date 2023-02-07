<?php

declare(strict_types=1);

namespace PhpProject\TestSuite\Resolver\PhpUnit;

use PhpProject\SourceCode\Classes\Filter\Compound\CompoundMethodFilter;
use PhpProject\SourceCode\Classes\Filter\Configured\ImplementsClassFilter;
use PhpProject\SourceCode\Classes\Filter\Configured\NameStartsWithMethodFilter;
use PhpProject\SourceCode\Classes\Filter\Configured\PhpAttributeMethodFilter;
use PhpProject\SourceCode\Classes\Filter\Configured\PhpDocAnnotationMethodFilter;
use PhpProject\SourceCode\Classes\Filter\Filter;
use PhpProject\SourceCode\Files\Constraint\CompoundFileConstraint;
use PhpProject\SourceCode\Files\Constraint\FileConstraint;
use PhpProject\SourceCode\Files\Constraint\NullFileConstraint;
use PhpProject\SourceCode\Files\Constraint\PrefixFileConstraint;
use PhpProject\SourceCode\Files\Constraint\SuffixFileConstraint;
use PhpProject\SourceCode\Files\Manager\FileManager;
use PhpProject\SourceCode\Files\Path\RelativePath;
use PhpProject\SourceCode\Files\SourceFiles;
use PhpProject\TestSuite\Configuration\TestSuiteConfig;
use PhpProject\TestSuite\Configuration\TestSuitesConfig;
use PhpProject\TestSuite\Resolver\TestSuiteConfigurationResolver;
use PHPUnit\Framework\Attributes\Test as TestAttribute;
use PHPUnit\Framework\Test;
use PHPUnit\TextUI\Configuration\File;
use PHPUnit\TextUI\Configuration\FileCollection;
use PHPUnit\TextUI\Configuration\TestDirectory;
use PHPUnit\TextUI\Configuration\TestDirectoryCollection;
use PHPUnit\TextUI\Configuration\TestFile;
use PHPUnit\TextUI\Configuration\TestFileCollection;
use PHPUnit\TextUI\Configuration\TestSuite as PhpUnitTestSuite;
use PHPUnit\TextUI\Configuration\TestSuiteCollection;
use PHPUnit\Util\VersionComparisonOperator;

final readonly class PhpUnitTestSuiteConfigurationResolver implements TestSuiteConfigurationResolver
{
    private const METHOD_PREFIX     = 'test';
    private const METHOD_ANNOTATION = '@test';

    public function __construct(
        private PhpUnitConfigurationLoader $configurationLoader,
        private FileManager $projectFileManager,
        private ?RelativePath $configFile = null,
    ) {
    }

    public function getTestSuites(): TestSuitesConfig
    {
        return new TestSuitesConfig(
            array_map(
                fn (PhpUnitTestSuite $testSuite): TestSuiteConfig => new TestSuiteConfig(
                    $testSuite->name(),
                    $this->getTestSuiteFiles($testSuite),
                    self::getFilter()
                ),
                $this->getTestSuiteCollection()->asArray()
            )
        );
    }

    private function getTestSuiteCollection(): TestSuiteCollection
    {
        return $this->configurationLoader->load(
            $this->projectFileManager->getAbsolutePath($this->configFile ?? RelativePath::raw('phpunit.xml'))
        )->testSuite();
    }

    private function getTestSuiteFiles(PhpUnitTestSuite $testSuite): SourceFiles
    {
        $sources = $this->getDirectoriesFiles($testSuite->directories());
        $sources = $sources->remove($this->getExcludedFiles($testSuite->exclude()));

        return $sources->add($this->getIncludedFiles($testSuite->files()));
    }

    private function getDirectoriesFiles(TestDirectoryCollection $directories): SourceFiles
    {
        return SourceFiles::fromSourceFiles(
            ...array_map(
                fn (TestDirectory $directory): SourceFiles => $this->getDirectoryFiles($directory),
                array_filter(
                    $directories->asArray(),
                    static fn (TestDirectory $directory): bool => self::isPhpVersionConstraintSatisfied(
                        $directory->phpVersion(),
                        $directory->phpVersionOperator()
                    )
                )
            )
        );
    }

    private function getDirectoryFiles(TestDirectory $directory): SourceFiles
    {
        $directoryPath = $this->projectFileManager->getPath($directory->path());
        $constraints   = $this->getFileConstraints($directory);

        return $this->projectFileManager->getSourceFilesFromPath($directoryPath, $constraints);
    }

    private function getExcludedFiles(FileCollection $files): SourceFiles
    {
        $sourceFiles = array_map(
            fn (File $file): SourceFiles => $this->getExcludedFile($file),
            $files->asArray()
        );

        return SourceFiles::fromSourceFiles(...$sourceFiles);
    }

    private function getExcludedFile(File $file): SourceFiles
    {
        $filePath = $this->projectFileManager->getPath($file->path());

        return $this->projectFileManager->getSourceFilesFromPath($filePath);
    }

    private function getIncludedFiles(TestFileCollection $files): SourceFiles
    {
        $filteredFiles = array_filter(
            $files->asArray(),
            static fn (TestFile $file): bool => self::isPhpVersionConstraintSatisfied(
                $file->phpVersion(),
                $file->phpVersionOperator()
            )
        );

        $sourceFiles = array_map(
            fn (TestFile $file): SourceFiles => $this->getIncludedFile($file),
            $filteredFiles
        );

        return SourceFiles::fromSourceFiles(...$sourceFiles);
    }

    private function getIncludedFile(TestFile $file): SourceFiles
    {
        $filePath = $this->projectFileManager->getPath($file->path());

        return $this->projectFileManager->getSourceFilesFromPath($filePath);
    }

    private function getFileConstraints(TestDirectory $directory): FileConstraint
    {
        $constraints = [];

        if ($directory->prefix() !== '') {
            $constraints[] = new PrefixFileConstraint($directory->prefix());
        }

        if ($directory->suffix() !== '') {
            $constraints[] = new SuffixFileConstraint($directory->suffix());
        }

        if ($constraints === []) {
            return new NullFileConstraint();
        }

        return new CompoundFileConstraint(...$constraints);
    }

    private static function isPhpVersionConstraintSatisfied(string $phpVersion, VersionComparisonOperator $comparisonOperator): bool
    {
        return version_compare(\PHP_VERSION, $phpVersion, $comparisonOperator->asString());
    }

    private static function getFilter(): Filter
    {
        return new Filter(
            new ImplementsClassFilter(Test::class),
            CompoundMethodFilter::Or(
                new NameStartsWithMethodFilter(self::METHOD_PREFIX),
                new PhpDocAnnotationMethodFilter(self::METHOD_ANNOTATION),
                new PhpAttributeMethodFilter(TestAttribute::class)
            )
        );
    }
}
