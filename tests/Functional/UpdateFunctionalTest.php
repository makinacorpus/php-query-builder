<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Functional;

use MakinaCorpus\QueryBuilder\Query\Update;
use MakinaCorpus\QueryBuilder\Tests\Bridge\Doctrine\DoctrineTestCase;
use MakinaCorpus\QueryBuilder\Vendor;

class UpdateFunctionalTest extends DoctrineTestCase
{
    /** @before */
    protected function createSchema(): void
    {
        try {
            $this->getDatabaseSession()->executeStatement(
                <<<SQL
                DROP TABLE foo
                SQL
            );
        } catch (\Throwable) {}

        try {
            $this->getDatabaseSession()->executeStatement(
                <<<SQL
                DROP TABLE bar
                SQL
            );
        } catch (\Throwable) {}

        switch ($this->getDatabaseSession()->getVendorName()) {

            case Vendor::MARIADB:
            case Vendor::MYSQL:
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE foo (
                        id int NOT NULL,
                        name text DEFAULT NULL,
                        date datetime DEFAULT now()
                    )
                    SQL
                );
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE bar (
                        foo_id int NOT NULL,
                        data text DEFAULT NULL
                    )
                    SQL
                );
                break;

            case Vendor::SQLSERVER:
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE foo (
                        id int NOT NULL,
                        name nvarchar(500) DEFAULT NULL,
                        date datetime2(6) DEFAULT current_timestamp
                    )
                    SQL
                );
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE bar (
                        foo_id int NOT NULL,
                        data nvarchar(500) DEFAULT NULL
                    )
                    SQL
                );
                break;

            default:
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE foo (
                        id int NOT NULL,
                        name text DEFAULT NULL,
                        date timestamp DEFAULT current_timestamp
                    )
                    SQL
                );
                $this->getDatabaseSession()->executeStatement(
                    <<<SQL
                    CREATE TABLE bar (
                        foo_id int NOT NULL,
                        data text DEFAULT NULL
                    )
                    SQL
                );
                break;
        }

        $this->getDatabaseSession()->executeStatement(
            <<<SQL
            INSERT INTO foo (id, name, date)
            VALUES
                (1, 'Lonny', '1972-12-09 04:30:00'),
                (2, 'Pierre', '1983-03-22 08:25:00'),
                (3, 'Simon', '1999-07-12 14:00:00'),
                (4, 'David', '1955-12-12 15:07:23')
            SQL
        );

        $this->getDatabaseSession()->executeStatement(
            <<<SQL
            INSERT INTO bar (foo_id, data)
            VALUES
                (1, 'Moutarde'),
                (2, 'Bleu'),
                (3, 'Fushia')
            SQL
        );
    }

    public function testUpdate(): void
    {
        $update = new Update('foo');
        $update->set('date', new \DateTimeImmutable('2012-08-21 12:32:54'));

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }

    public function testReturning(): void
    {
        $this->skipIfDatabase(Vendor::MARIADB, 'MariaDB does not support RETURNING|OUPUT');
        $this->skipIfDatabase(Vendor::MYSQL, 'MariaDB does not support RETURNING|OUPUT');

        $update = new Update('foo');
        $update
            ->set('date', new \DateTimeImmutable('2012-08-21 12:32:54'))
            ->returning('id')
            ->returning('name')
        ;

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }

    public function testReturningExpression(): void
    {
        $this->skipIfDatabase(Vendor::MARIADB, 'MariaDB does not support RETURNING|OUPUT');
        $this->skipIfDatabase(Vendor::MYSQL, 'MariaDB does not support RETURNING|OUPUT');
        $this->skipIfDatabase(Vendor::SQLSERVER, 'SQL Server has its own test for this.');

        $update = new Update('foo');
        $expr = $update->expression();

        $update->set('date', new \DateTimeImmutable('2012-08-21 12:32:54'));
        $update->returning(
            $expr->concat(
                $expr->column('id'),
                $expr->column('name'),
            )
        );

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }

    public function testReturningExpressionSqlServer(): void
    {
        $this->skipIfDatabaseNot(Vendor::SQLSERVER);

        $update = new Update('foo');
        $expr = $update->expression();

        $update->set('date', new \DateTimeImmutable('2012-08-21 12:32:54'));
        $update->returning(
            $expr->concat(
                $expr->raw('inserted.id'),
                $expr->raw('inserted.name'),
            )
        );

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }

    public function testReturningStar(): void
    {
        $this->skipIfDatabase(Vendor::MARIADB, 'MariaDB does not support RETURNING|OUPUT');
        $this->skipIfDatabase(Vendor::MYSQL, 'MariaDB does not support RETURNING|OUPUT');

        $update = new Update('foo');
        $update->set('date', new \DateTimeImmutable('2012-08-21 12:32:54'));
        $update->returning();

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }

    public function testWhere(): void
    {
        $update = new Update('foo');
        $update->set('date', new \DateTimeImmutable('2012-08-21 12:32:54'));
        $update->where('id', 2);

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }

    public function testFromWith(): void
    {
        $this->skipIfDatabase(Vendor::MARIADB);
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

        $update = new Update('foo');
        $expr = $update->expression();

        $update->createWith('colours', 'bar')->column('*');

        $update
            ->join('colours', 'temp.foo_id = foo.id', 'temp')
            ->set('name', $expr->concat($expr->column('foo.name'), $expr->column('temp.data')))
        ;

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }

    public function testFromWithJoin(): void
    {
        $this->skipIfDatabase(Vendor::MARIADB);
        $this->skipIfDatabaseLessThan(Vendor::MYSQL, '8.0');

        $update = new Update('foo');
        $expr = $update->expression();

        $update->createWith('colours', 'bar')->column('*');

        $update
            ->join('colours', 'temp1.foo_id = foo.id', 'temp1')
            ->join('bar', 'temp2.foo_id = temp1.foo_id', 'temp2')
            ->set('name', $expr->concat($expr->column('foo.name'), $expr->column('temp2.data')))
        ;

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }

    public function testFrom(): void
    {
        $update = new Update('foo');
        $expr = $update->expression();

        $update
            ->join('bar', 'temp.foo_id = foo.id', 'temp')
            ->set('name', $expr->concat($expr->column('foo.name'), $expr->column('temp.data')))
        ;

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }

    public function testFromJoin(): void
    {
        $this->skipIfDatabase(Vendor::SQLSERVER, 'Temporarily disabled due to a bug');

        $update = new Update('foo');
        $expr = $update->expression();

        $update
            ->join('foo', 'temp1.id = foo.id', 'temp1')
            ->join('bar', 'temp2.foo_id = temp1.id', 'temp2')
            ->set('name', $expr->concat($expr->column('foo.name'), $expr->column('temp2.data')))
        ;

        $this->executeStatement($update);

        self::expectNotToPerformAssertions();
    }
}
