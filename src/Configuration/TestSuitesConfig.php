<?php

declare(strict_types=1);

namespace PhpProject\TestSuite\Configuration;

/**
 * @template-implements \IteratorAggregate<int, TestSuiteConfig>
 */
final readonly class TestSuitesConfig implements \Countable, \IteratorAggregate
{
    /**
     * @param array<TestSuiteConfig> $testSuiteConfig
     */
    public function __construct(
        private array $testSuiteConfig
    ) {
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->testSuiteConfig);
    }

    public function count(): int
    {
        return \count($this->testSuiteConfig);
    }

    /**
     * @return array<TestSuiteConfig>
     */
    public function asArray(): array
    {
        return $this->testSuiteConfig;
    }
}
