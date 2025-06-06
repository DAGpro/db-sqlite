<?php

declare(strict_types=1);

namespace Yiisoft\Db\Sqlite\Tests;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use Yiisoft\Db\Schema\Column\ColumnInterface;
use Yiisoft\Db\Sqlite\Column\ColumnFactory;
use Yiisoft\Db\Sqlite\Tests\Provider\ColumnFactoryProvider;
use Yiisoft\Db\Sqlite\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\AbstractColumnFactoryTest;

/**
 * @group sqlite
 */
final class ColumnFactoryTest extends AbstractColumnFactoryTest
{
    use TestTrait;

    protected function getColumnFactoryClass(): string
    {
        return ColumnFactory::class;
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'dbTypes')]
    public function testFromDbType(string $dbType, string $expectedType, string $expectedInstanceOf): void
    {
        parent::testFromDbType($dbType, $expectedType, $expectedInstanceOf);
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'definitions')]
    public function testFromDefinition(string $definition, ColumnInterface $expected): void
    {
        parent::testFromDefinition($definition, $expected);
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'pseudoTypes')]
    public function testFromPseudoType(string $pseudoType, ColumnInterface $expected): void
    {
        parent::testFromPseudoType($pseudoType, $expected);
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'types')]
    public function testFromType(string $type, string $expectedType, string $expectedInstanceOf): void
    {
        parent::testFromType($type, $expectedType, $expectedInstanceOf);
    }

    #[DataProviderExternal(ColumnFactoryProvider::class, 'defaultValueRaw')]
    public function testFromTypeDefaultValueRaw(string $type, string|null $defaultValueRaw, mixed $expected): void
    {
        parent::testFromTypeDefaultValueRaw($type, $defaultValueRaw, $expected);
    }
}
