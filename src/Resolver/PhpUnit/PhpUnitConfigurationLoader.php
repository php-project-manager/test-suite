<?php

declare(strict_types=1);

namespace PhpProject\TestSuite\Resolver\PhpUnit;

use PhpProject\SourceCode\Files\Path\AbsolutePath;
use PHPUnit\TextUI\XmlConfiguration\Configuration;
use PHPUnit\TextUI\XmlConfiguration\Loader;

final class PhpUnitConfigurationLoader
{
    public function load(AbsolutePath $configFilePath): Configuration
    {
        return (new Loader())->load((string) $configFilePath);
    }
}
