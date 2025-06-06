<?php

declare(strict_types=1);

namespace Yiisoft\Db\Sqlite\Tests\Provider;

use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\QueryBuilder\Condition\InCondition;
use Yiisoft\Db\Sqlite\Tests\Support\TestTrait;
use Yiisoft\Db\Tests\Support\TraversableObject;

use function array_replace;

final class QueryBuilderProvider extends \Yiisoft\Db\Tests\Provider\QueryBuilderProvider
{
    use TestTrait;

    protected static string $driverName = 'sqlite';
    protected static string $likeEscapeCharSql = " ESCAPE '\\'";

    public static function buildCondition(): array
    {
        $buildCondition = parent::buildCondition();

        unset(
            $buildCondition['inCondition-custom-1'],
            $buildCondition['inCondition-custom-2'],
            $buildCondition['inCondition-custom-6'],
        );

        return [
            ...$buildCondition,
            'composite in using array objects' => [
                [
                    'in',
                    new TraversableObject(['id', 'name']),
                    new TraversableObject([['id' => 1, 'name' => 'oy'], ['id' => 2, 'name' => 'yo']]),
                ],
                '(([[id]] = :qp0 AND [[name]] = :qp1) OR ([[id]] = :qp2 AND [[name]] = :qp3))',
                [':qp0' => 1, ':qp1' => 'oy', ':qp2' => 2, ':qp3' => 'yo'],
            ],
            'composite in' => [
                ['in', ['id', 'name'], [['id' => 1, 'name' => 'oy']]],
                '(([[id]] = :qp0 AND [[name]] = :qp1))',
                [':qp0' => 1, ':qp1' => 'oy'],
            ],
            'composite in with Expression' => [
                ['in',
                    [new Expression('id'), new Expression('name')],
                    [['id' => 1, 'name' => 'oy']],
                ],
                '((id = :qp0 AND name = :qp1))',
                [':qp0' => 1, ':qp1' => 'oy'],
            ],
            'composite in array values no exist' => [
                ['in', ['id', 'name', 'email'], [['id' => 1, 'name' => 'oy']]],
                '(([[id]] = :qp0 AND [[name]] = :qp1 AND [[email]] IS NULL))',
                [':qp0' => 1, ':qp1' => 'oy'],
            ],
            [
                ['in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                '(([[id]] = :qp0 AND [[name]] = :qp1) OR ([[id]] = :qp2 AND [[name]] = :qp3))',
                [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar'],
            ],
            [
                ['not in', ['id', 'name'], [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]],
                '(([[id]] != :qp0 OR [[name]] != :qp1) AND ([[id]] != :qp2 OR [[name]] != :qp3))',
                [':qp0' => 1, ':qp1' => 'foo', ':qp2' => 2, ':qp3' => 'bar'],
            ],
            'inCondition-custom-3' => [
                new InCondition(['id', 'name'], 'in', [['id' => 1]]),
                '(([[id]] = :qp0 AND [[name]] IS NULL))',
                [':qp0' => 1],
            ],
            'inCondition-custom-4' => [
                new InCondition(['id', 'name'], 'in', [['name' => 'oy']]),
                '(([[id]] IS NULL AND [[name]] = :qp0))',
                [':qp0' => 'oy'],
            ],
            'inCondition-custom-5' => [
                new InCondition(['id', 'name'], 'in', [['id' => 1, 'name' => 'oy']]),
                '(([[id]] = :qp0 AND [[name]] = :qp1))',
                [':qp0' => 1, ':qp1' => 'oy'],
            ],
            'like-custom-1' => [['like', 'a', 'b'], '[[a]] LIKE :qp0 ESCAPE \'\\\'', [':qp0' => new Param('%b%', DataType::STRING)]],
            'like-custom-2' => [
                ['like', 'a', new Expression(':qp0', [':qp0' => '%b%'])],
                '[[a]] LIKE :qp0 ESCAPE \'\\\'',
                [':qp0' => '%b%'],
            ],
            'like-custom-3' => [
                ['like', new Expression('CONCAT(col1, col2)'), 'b'],
                'CONCAT(col1, col2) LIKE :qp0 ESCAPE \'\\\'',
                [':qp0' => new Param('%b%', DataType::STRING)],
            ],
        ];
    }

    public static function insert(): array
    {
        $insert = parent::insert();

        $insert['empty columns'][3] = <<<SQL
        INSERT INTO `customer` DEFAULT VALUES
        SQL;

        return $insert;
    }

    public static function upsert(): array
    {
        $concreteData = [
            'regular values' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `address`, `status`, `profile_id`) AS (VALUES (:qp0, :qp1, :qp2, :qp3)) UPDATE `T_upsert` SET `address`=(SELECT `address` FROM `EXCLUDED`), `status`=(SELECT `status` FROM `EXCLUDED`), `profile_id`=(SELECT `profile_id` FROM `EXCLUDED`) WHERE `T_upsert`.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3);
                SQL,
            ],
            'regular values with unique at not the first position' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`address`, `email`, `status`, `profile_id`) AS (VALUES (:qp0, :qp1, :qp2, :qp3)) UPDATE `T_upsert` SET `address`=(SELECT `address` FROM `EXCLUDED`), `status`=(SELECT `status` FROM `EXCLUDED`), `profile_id`=(SELECT `profile_id` FROM `EXCLUDED`) WHERE `T_upsert`.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO `T_upsert` (`address`, `email`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3);
                SQL,
            ],
            'regular values with update part' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `address`, `status`, `profile_id`) AS (VALUES (:qp0, :qp1, :qp2, :qp3)) UPDATE `T_upsert` SET `address`=:qp4, `status`=:qp5, `orders`=T_upsert.orders + 1 WHERE `T_upsert`.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3);
                SQL,
            ],
            'regular values without update part' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO `T_upsert` (`email`, `address`, `status`, `profile_id`) VALUES (:qp0, :qp1, :qp2, :qp3)
                SQL,
            ],
            'query' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `status`) AS (SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1) UPDATE `T_upsert` SET `status`=(SELECT `status` FROM `EXCLUDED`) WHERE `T_upsert`.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1;
                SQL,
            ],
            'query with update part' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `status`) AS (SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1) UPDATE `T_upsert` SET `address`=:qp1, `status`=:qp2, `orders`=T_upsert.orders + 1 WHERE `T_upsert`.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1;
                SQL,
            ],
            'query without update part' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO `T_upsert` (`email`, `status`) SELECT `email`, 2 AS `status` FROM `customer` WHERE `name`=:qp0 LIMIT 1
                SQL,
            ],
            'values and expressions' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `ts`) AS (VALUES (:qp0, CURRENT_TIMESTAMP)) UPDATE {{%T_upsert}} SET `ts`=(SELECT `ts` FROM `EXCLUDED`) WHERE {{%T_upsert}}.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP);
                SQL,
            ],
            'values and expressions with update part' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, `ts`) AS (VALUES (:qp0, CURRENT_TIMESTAMP)) UPDATE {{%T_upsert}} SET `orders`=T_upsert.orders + 1 WHERE {{%T_upsert}}.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP);
                SQL,
            ],
            'values and expressions without update part' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO {{%T_upsert}} (`email`, `ts`) VALUES (:qp0, CURRENT_TIMESTAMP)
                SQL,
            ],
            'query, values and expressions with update part' => [
                3 => <<<SQL
                WITH "EXCLUDED" (`email`, [[ts]]) AS (SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]]) UPDATE {{%T_upsert}} SET `ts`=:qp1, `orders`=T_upsert.orders + 1 WHERE {{%T_upsert}}.`email`=(SELECT `email` FROM `EXCLUDED`); INSERT OR IGNORE INTO {{%T_upsert}} (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]];
                SQL,
            ],
            'query, values and expressions without update part' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO {{%T_upsert}} (`email`, [[ts]]) SELECT :phEmail AS `email`, CURRENT_TIMESTAMP AS [[ts]]
                SQL,
            ],
            'no columns to update' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO `T_upsert_1` (`a`) VALUES (:qp0)
                SQL,
            ],
            'no columns to update with unique' => [
                3 => <<<SQL
                INSERT OR IGNORE INTO {{%T_upsert}} (`email`) VALUES (:qp0)
                SQL,
            ],
            'no unique columns in table - simple insert' => [
                3 => <<<SQL
                INSERT INTO {{%animal}} (`type`) VALUES (:qp0)
                SQL,
            ],
        ];

        $upsert = parent::upsert();

        foreach ($concreteData as $testName => $data) {
            $upsert[$testName] = array_replace($upsert[$testName], $data);
        }

        return $upsert;
    }

    public static function buildColumnDefinition(): array
    {
        $values = parent::buildColumnDefinition();

        $values[PseudoType::PK][0] = 'integer PRIMARY KEY AUTOINCREMENT NOT NULL';
        $values[PseudoType::UPK][0] = 'integer PRIMARY KEY AUTOINCREMENT NOT NULL';
        $values[PseudoType::BIGPK][0] = 'integer PRIMARY KEY AUTOINCREMENT NOT NULL';
        $values[PseudoType::UBIGPK][0] = 'integer PRIMARY KEY AUTOINCREMENT NOT NULL';
        $values[PseudoType::UUID_PK][0] = 'blob(16) PRIMARY KEY NOT NULL DEFAULT (randomblob(16))';
        $values[PseudoType::UUID_PK_SEQ][0] = 'blob(16) PRIMARY KEY NOT NULL DEFAULT (randomblob(16))';
        $values['primaryKey()'][0] = 'integer PRIMARY KEY AUTOINCREMENT NOT NULL';
        $values['primaryKey(false)'][0] = 'integer PRIMARY KEY NOT NULL';
        $values['smallPrimaryKey()'][0] = 'integer PRIMARY KEY AUTOINCREMENT NOT NULL';
        $values['smallPrimaryKey(false)'][0] = 'smallint PRIMARY KEY NOT NULL';
        $values['bigPrimaryKey()'][0] = 'integer PRIMARY KEY AUTOINCREMENT NOT NULL';
        $values['bigPrimaryKey(false)'][0] = 'bigint PRIMARY KEY NOT NULL';
        $values['uuidPrimaryKey()'][0] = 'blob(16) PRIMARY KEY NOT NULL DEFAULT (randomblob(16))';
        $values['uuidPrimaryKey(false)'][0] = 'blob(16) PRIMARY KEY NOT NULL';
        $values['money()'][0] = 'decimal(19,4)';
        $values['money(10)'][0] = 'decimal(10,4)';
        $values['money(10,2)'][0] = 'decimal(10,2)';
        $values['money(null)'][0] = 'decimal';
        $values['binary()'][0] = 'blob';
        $values['binary(1000)'][0] = 'blob(1000)';
        $values['uuid()'][0] = 'blob(16)';
        $values["comment('comment')"][0] = 'varchar(255) /* comment */';
        $values['integer()->primaryKey()'][0] = 'integer PRIMARY KEY NOT NULL';
        $values['string()->primaryKey()'][0] = 'varchar(255) PRIMARY KEY NOT NULL';
        $values['unsigned()'][0] = 'integer';

        return $values;
    }

    public static function prepareParam(): array
    {
        $values = parent::prepareParam();

        $values['binary'][0] = "x'737472696e67'";

        return $values;
    }

    public static function prepareValue(): array
    {
        $values = parent::prepareValue();

        $values['binary'][0] = "x'737472696e67'";
        $values['paramBinary'][0] = "x'737472696e67'";

        return $values;
    }
}
