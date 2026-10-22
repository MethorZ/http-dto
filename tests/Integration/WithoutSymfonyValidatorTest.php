<?php

declare(strict_types=1);

namespace MethorZ\Dto\Tests\Integration;

use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function implode;
use function var_export;

use const PHP_BINARY;

/**
 * Builds the Mezzio container in a separate PHP process whose autoloader refuses
 * every Symfony Validator class, mirroring an application that does not install
 * the optional symfony/validator package.
 */
final class WithoutSymfonyValidatorTest extends TestCase
{
    public function testContainerFallsBackToNullValidatorWhenSymfonyValidatorIsNotInstalled(): void
    {
        $autoload = var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true);
        $script = <<<PHP
            \$loader = require {$autoload};
            \$loader->unregister();
            spl_autoload_register(static function (string \$class) use (\$loader): void {
                if (!str_starts_with(\$class, 'Symfony\\\\Component\\\\Validator\\\\')) {
                    \$loader->loadClass(\$class);
                }
            });

            \$config = (new MethorZ\Dto\Integration\Mezzio\ConfigProvider())->getDependencies();
            \$container = new Laminas\ServiceManager\ServiceManager(\$config);

            echo \$container->get(MethorZ\Dto\Validator\ValidatorInterface::class)::class;
            PHP;

        $output = [];
        $exitCode = 0;
        exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($script) . ' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
        $this->assertSame(['MethorZ\Dto\Validator\NullValidator'], $output);
    }
}
