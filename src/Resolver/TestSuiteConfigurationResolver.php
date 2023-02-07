<?php

declare(strict_types=1);

namespace PhpProject\TestSuite\Resolver;

use PhpProject\TestSuite\Configuration\TestSuitesConfig;

interface TestSuiteConfigurationResolver
{
    public function getTestSuites(): TestSuitesConfig;
}
