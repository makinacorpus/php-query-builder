<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Schema;

use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Error\Bridge\TableDoesNotExistError;
use MakinaCorpus\QueryBuilder\Schema\ForeignKey;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;

abstract class AbstractSchemaTestCase extends FunctionalTestCase
{
    /** @before */
    protected function createSchema(): void
    {
        foreach ([
            'no_pk_table',
            'renamed_table',
            'renamed_table_new_name',
            'test_table',
            'test_table_tmp',
            'test_table_pk',
            'test_table_fk',
            'test_table_uq',
            'test_table_idx',
            'user_address',
            'users',
            'org',
        ] as $table) {
            try {
                $this->getBridge()->executeStatement('DROP TABLE ?::table', [$table]);
            } catch (TableDoesNotExistError) {}
        }

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
                        username varchar(255) DEFAULT NULL,
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
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE no_pk_table (
                        id int UNIQUE NOT NULL,
                        name text DEFAULT NULL
                    )
                    SQL
                );
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE renamed_table (
                        id int UNIQUE NOT NULL,
                        name text DEFAULT NULL
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
                        username varchar(255) DEFAULT NULL,
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
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE no_pk_table (
                        id int UNIQUE NOT NULL,
                        name text DEFAULT NULL
                    )
                    SQL
                );
                $this->getBridge()->executeStatement(
                    <<<SQL
                    CREATE TABLE renamed_table (
                        id int UNIQUE NOT NULL,
                        name text DEFAULT NULL
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
                ->addColumn('users', 'age', 'int', true)
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
                ->addColumn('users', 'age', 'int', true, '12')
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
                ->addColumn('users', 'age', 'int', false)
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
                ->addColumn('users', 'age', 'int', false, '12')
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
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->renameColumn('user_address', 'city', 'locality')
            ->commit()
        ;

        $table = $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
        ;

        self::assertSame(['id', 'org_id', 'user_id', 'locality', 'country'], $table->getColumnNames());
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
                ->addForeignKey('user_address', ['org_id'], 'org', ['id'])
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
                ->addForeignKey('user_address', ['org_id'], 'org', ['id'], 'user_address_org_org_id_fk')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->dropForeignKey('user_address', 'user_address_org_org_id_fk')
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
                ->createIndex('users', ['email'], 'users_email_idx')
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testIndexDrop(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->createIndex('users', ['email'], 'users_email_idx')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->dropIndex('users', 'users_email_idx')
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testIndexRename(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->createIndex('no_pk_table', ['id'], 'my_foo_unique_index')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->dropIndex('no_pk_table', 'my_foo_unique_index')
            ->commit()
        ;

        // @todo Implement indexes read in schema manager.
        self::expectNotToPerformAssertions();
    }

    public function testPrimaryKeyAdd(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->addPrimaryKey('no_pk_table', ['id'])
            ->commit()
        ;

        $primaryKey = $this
            ->getSchemaManager()
            ->getTable('test_db', 'no_pk_table')
            ->getPrimaryKey()
        ;

        self::assertSame(['id'], $primaryKey->getColumnNames());
    }

    public function testPrimaryKeyDrop(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->addPrimaryKey('no_pk_table', ['id'], 'no_pk_table_pkey_with_name')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->dropPrimaryKey('no_pk_table', 'no_pk_table_pkey_with_name')
            ->commit()
        ;

        $primaryKey = $this
            ->getSchemaManager()
            ->getTable('test_db', 'no_pk_table')
            ->getPrimaryKey()
        ;

        self::assertNull($primaryKey);
    }

    public function testTableCreate(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->createTable('test_table')
                    ->column('foo', 'int8', false)
                    ->column('bar', 'text', true)
                ->endTable()
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'test_table')
            ->getColumn('bar')
        ;

        self::assertTrue($column->isNullable());
        self::assertStringContainsString('text', $column->getValueType());
    }

    public function testTableCreateTemporary(): void
    {
        self::markTestIncomplete("PostgreSQL refuses to create a temporary relation in a non-temporary schema ?!");

        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->createTable('test_table_tmp')
                    ->temporary()
                    ->column('foo', 'int8', false)
                    ->column('bar', 'varchar(255)', true)
                ->endTable()
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testTableCreateWithPrimaryKey(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->createTable('test_table_pk')
                    ->column('foo', 'int8', false)
                    ->column('bar', 'varchar(255)', true)
                    ->primaryKey(['foo', 'bar'])
                ->endTable()
            ->commit()
        ;

        $primaryKey = $this
            ->getSchemaManager()
            ->getTable('test_db', 'test_table_pk')
            ->getPrimaryKey()
        ;

        self::assertSame(['foo', 'bar'], $primaryKey->getColumnNames());
    }

    public function testTableCreateWithForeignKey(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->createTable('test_table_fk')
                    ->column('foo', 'int', false)
                    ->column('bar', 'text', true)
                    ->foreignKey('org', ['foo' => 'id'])
                ->endTable()
            ->commit()
        ;

        $firstForeignKey = $this
            ->getSchemaManager()
            ->getTable('test_db', 'test_table_fk')
            ->getForeignKeys()[0] ?? null
        ;
        \assert($firstForeignKey instanceof ForeignKey);

        self::assertSame(['foo'], $firstForeignKey->getColumnNames());
        self::assertSame(['id'], $firstForeignKey->getForeignColumnNames());
    }

    public function testTableCreateWithUniqueKey(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->createTable('test_table_fk')
                    ->column('foo', 'int', false)
                    ->column('bar', 'varchar(255)', true)
                    ->uniqueKey(['bar'])
                ->endTable()
            ->commit()
        ;

        // @todo We need to test this once the schema read api gets more complete.
        self::expectNotToPerformAssertions();
    }

    public function testTableCreateWithIndex(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->createTable('test_table_fk')
                    ->column('foo', 'int', false)
                    ->column('bar', 'varchar(255)', true)
                    ->uniqueKey(['bar'])
                    ->index(['foo'])
                    ->index(['foo', 'bar'])
                ->endTable()
            ->commit()
        ;

        // @todo We need to test this once the schema read api gets more complete.
        self::expectNotToPerformAssertions();
    }

    public function testTableCreateWithAll(): void
    {
        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testTableDrop(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->dropTable('user_address')
            ->commit()
        ;

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessage("Table 'test_db.public.user_address' does not exist");
        $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
        ;
    }

    public function testTableRename(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->renameTable('renamed_table', 'renamed_table_new_name')
            ->commit()
        ;

        $table = $this
            ->getSchemaManager()
            ->getTable('test_db', 'renamed_table_new_name')
        ;

        self::assertSame('renamed_table_new_name', $table->getName());

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessage("Table 'test_db.public.renamed_table' does not exist");
        $this
            ->getSchemaManager()
            ->getTable('test_db', 'renamed_table')
        ;
    }

    public function testUniqueKeyAdd(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->addUniqueKey('users', ['username'])
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testUniqueKeyAddNamed(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->addUniqueKey('users', ['username'], 'users_unique_username_idx')
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testUniqueKeyDrop(): void
    {
        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->addUniqueKey('users', ['username'], 'users_unique_username_idx')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify('test_db')
                ->dropUniqueKey('users', 'users_unique_username_idx')
            ->commit()
        ;

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
