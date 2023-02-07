<?php

declare(strict_types=1);

namespace PhpProject\TestSuite\Configuration;

use PhpProject\SourceCode\Classes\Filter\Filter;
use PhpProject\SourceCode\Files\SourceFiles;

final readonly class TestSuiteConfig
{
    public function __construct(
        public string $name,
        public SourceFiles $source,
        public Filter $filter
    ) {
    }
}
