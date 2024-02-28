<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Schema;

use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;

abstract class AbstractSchemaTestCase extends FunctionalTestCase
{
    /** @before */
    protected function createSchema(): void
    {
        try {
            $this->getBridge()->executeStatement(
                <<<SQL
                DROP TABLE user_address
                SQL
            );
        } catch (\Throwable) {}

        try {
            $this->getBridge()->executeStatement(
                <<<SQL
                DROP TABLE users
                SQL
            );
        } catch (\Throwable) {}

        try {
            $this->getBridge()->executeStatement(
                <<<SQL
                DROP TABLE org
                SQL
            );
        } catch (\Throwable) {}

        switch ($this->getBridge()->getServerFlavor()) {

            case Platform::MARIADB:
            case Platform::MYSQL:
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE org (
                        id int UNIQUE NOT NULL,
                        dept varchar(255) NOT NULL,
                        role varchar(255) NOT NULL,
                        name text DEFAULT NULL,
                        balance decimal(10,2) NOT NULL DEFAULT 0.0,
                        employes int unsigned NOT NULL DEFAULT 0, 
                        PRIMARY KEY (dept, role)
                    )
                    SQL
                );
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE users (
                        id int UNIQUE NOT NULL auto_increment PRIMARY KEY,
                        org_id INT DEFAULT NULL,
                        name text DEFAULT NULL,
                        email varchar(255) UNIQUE NOT NULL,
                        date datetime DEFAULT now(),
                        CONSTRAINT users_org_id FOREIGN KEY (org_id)
                            REFERENCES org (id)
                            ON DELETE CASCADE
                    )
                    SQL
                );
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE user_address (
                        id int UNIQUE NOT NULL auto_increment PRIMARY KEY,
                        org_id int DEFAULT NULL,
                        user_id int NOT NULL,
                        city text NOT NULL,
                        country varchar(6) NOT NULL DEFAULT 'fr',
                        CONSTRAINT user_address_user_id_fk FOREIGN KEY (user_id)
                            REFERENCES users (id)
                            ON DELETE CASCADE
                    )
                    SQL
                );
                break;

            case Platform::SQLSERVER:
                self::markTestIncomplete();
                break;

            case Platform::SQLITE:
                self::markTestIncomplete();
                break;

            case Platform::POSTGRESQL:
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE org (
                        id int UNIQUE NOT NULL,
                        dept varchar(255) NOT NULL,
                        role varchar(255) NOT NULL,
                        name text DEFAULT NULL,
                        balance decimal(10,2) NOT NULL DEFAULT 0.0,
                        employes int NOT NULL DEFAULT 0,
                        PRIMARY KEY (dept, role)
                    )
                    SQL
                );
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE users (
                        id serial UNIQUE NOT NULL PRIMARY KEY,
                        org_id INT DEFAULT NULL,
                        name text DEFAULT NULL,
                        email varchar(255) UNIQUE NOT NULL,
                        date timestamp with time zone DEFAULT current_timestamp,
                        CONSTRAINT users_org_id FOREIGN KEY (org_id)
                            REFERENCES org (id)
                            ON DELETE CASCADE
                    )
                    SQL
                );
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE user_address (
                        id serial UNIQUE NOT NULL PRIMARY KEY,
                        org_id int DEFAULT NULL,
                        user_id int NOT NULL,
                        city text NOT NULL,
                        country varchar(6) NOT NULL DEFAULT 'fr',
                        CONSTRAINT user_address_user_id_fk FOREIGN KEY (user_id)
                            REFERENCES users (id)
                            ON DELETE CASCADE
                    )
                    SQL
                );
                break;
        }
    }

    protected function getSchemaManager(): SchemaManager
    {
        try {
            return $this->getBridge()->getSchemaManager();
        } catch (UnsupportedFeatureError $e) {
            self::markTestSkipped($e->getMessage());
        }
    }

    public function testColumnAddNullable(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->columnAdd('users', 'age', 'int', true)
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'users')
            ->getColumn('age')
        ;

        // @todo missing default
        self::assertTrue($column->isNullable());
        self::assertStringContainsString('int', $column->getValueType());
    }

    public function testColumnAddNullableWithDefault(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->columnAdd('users', 'age', 'int', true, '12')
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'users')
            ->getColumn('age')
        ;

        // @todo missing default
        self::assertTrue($column->isNullable());
        self::assertStringContainsString('int', $column->getValueType());
    }

    public function testColumnAddNotNull(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->columnAdd('users', 'age', 'int', false)
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'users')
            ->getColumn('age')
        ;

        // @todo missing default
        self::assertFalse($column->isNullable());
        self::assertStringContainsString('int', $column->getValueType());
    }

    public function testColumnAddNotNullWithDefault(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->columnAdd('users', 'age', 'int', false, '12')
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'users')
            ->getColumn('age')
        ;

        // @todo missing default
        self::assertFalse($column->isNullable());
        self::assertStringContainsString('int', $column->getValueType());
    }

    public function testColumnModify(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testColumnDrop(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testColumnRename(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testConstraintDrop(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testConstraintModify(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testConstraintRename(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testForeignKeyAdd(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->foreignKeyAdd('user_address', ['org_id'], 'org', ['id'], null)
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testForeignKeyModify(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testForeignKeyDrop(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->foreignKeyAdd('user_address', ['org_id'], 'org', ['id'], null, 'user_address_org_org_id_fk')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->foreignKeyDrop('user_address', 'user_address_org_org_id_fk')
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testForeignKeyRename(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testIndexCreate(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->indexCreate('users', ['email'], 'users_email_idx')
            ->commit()
        ; 
    }

    public function testIndexDrop(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->indexCreate('users', ['email'], 'users_email_idx')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify('test_db')
            ->indexDrop('users', 'users_email_idx')
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testIndexRename(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testPrimaryKeyAdd(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testPrimaryKeyDrop(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testTableCreate(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testTableDrop(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testTableRename(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testUniqueConstraintAdd(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testUniqueConstraintDrop(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testListTables(): void
    {
        $tables = $this->getSchemaManager()->listTables('test_db');

        self::assertContains('org', $tables);
        self::assertContains('users', $tables);
        self::assertContains('user_address', $tables);
    }

    public function testColumnRaiseExceptionWhenNotExist(): void
    {
        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/Column .* does not exist on table/');

        $this
            ->getSchemaManager()
            ->getTable('test_db', 'org')
            ->getColumn('non_existing_column')
        ;
    }

    public function testColumnNumeric(): void
    {
        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'org')
            ->getColumn('balance')
        ;

        // @todo collation is hard, espcially cross-vendor.
        // self::assertNotEmpty($column->getCollation());
        self::assertContains($column->getValueType(), ['decimal', 'numeric']);
        self::assertSame('org', $column->getTable());
        self::assertSame('public', $column->getSchema());
        self::assertSame('column:test_db.public.org.balance', $column->toString());
        self::assertFalse($column->isNullable());
        self::assertSame(2, $column->getScale());
        self::assertSame(10, $column->getPrecision());
    }

    public function testColumnUnsigned(): void
    {
        $schemaManager = $this->getSchemaManager();

        if (!$schemaManager->supportsUnsigned()) {
            self::markTestSkipped();
        }

        $column = $schemaManager
            ->getTable('test_db', 'org')
            ->getColumn('employes')
        ;

        self::assertTrue($column->isUnsigned());
    }

    public function testColumnText(): void
    {
        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'org')
            ->getColumn('name')
        ;

        self::assertNotEmpty($column->getCollation());
        self::assertSame('text', $column->getValueType());
        self::assertSame('org', $column->getTable());
        self::assertSame('public', $column->getSchema());
        self::assertSame('column:test_db.public.org.name', $column->toString());
        self::assertTrue($column->isNullable());
        self::assertNull($column->getPrecision());
        self::assertNull($column->getScale());
    }

    public function testColumnDate(): void
    {
        self::markTestIncomplete();
    }

    public function testTable(): void
    {
        $table = $this
            ->getSchemaManager()
            ->getTable('test_db', 'org')
        ;

        self::assertSame(['dept', 'role'], $table->getPrimaryKey()?->getColumnNames());
        self::assertCount(6, $table->getColumns());
        self::assertEmpty($table->getForeignKeys());
        self::assertCount(1, $table->getReverseForeignKeys());
    }

    public function testTableRaiseExceptionWhenNotExist(): void
    {
        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches('/Table .* does not exist/');

        $this
            ->getSchemaManager()
            ->getTable('test_db', 'non_existing_table')
        ;
    }

    public function testTableForeignKeys(): void
    {
        $table = $this
            ->getSchemaManager()
            ->getTable('test_db', 'users')
        ;

        self::assertNotNull($foreignKey = ($table->getForeignKeys()[0] ?? null));
        self::assertSame(['org_id'], $foreignKey->getColumnNames());
        self::assertSame(['id'], $foreignKey->getForeignColumnNames());
        self::assertSame('users', $foreignKey->getTable());
        self::assertSame('org', $foreignKey->getForeignTable());

        self::assertNotNull($reverseForeignKey = ($table->getReverseForeignKeys()[0] ?? null));
        self::assertSame(['user_id'], $reverseForeignKey->getColumnNames());
        self::assertSame(['id'], $reverseForeignKey->getForeignColumnNames());
        self::assertSame('user_address', $reverseForeignKey->getTable());
        self::assertSame('users', $reverseForeignKey->getForeignTable());
    }

    public function testTableReverseForeignKeys(): void
    {
        self::markTestIncomplete();
    }
}
