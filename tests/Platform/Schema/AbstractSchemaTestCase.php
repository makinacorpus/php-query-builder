<?php

declare(strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests\Platform\Schema;

use MakinaCorpus\QueryBuilder\Error\Bridge\DatabaseObjectDoesNotExistError;
use MakinaCorpus\QueryBuilder\Error\QueryBuilderError;
use MakinaCorpus\QueryBuilder\Error\UnsupportedFeatureError;
use MakinaCorpus\QueryBuilder\Platform;
use MakinaCorpus\QueryBuilder\QueryBuilder;
use MakinaCorpus\QueryBuilder\Schema\Read\ForeignKey;
use MakinaCorpus\QueryBuilder\Schema\SchemaManager;
use MakinaCorpus\QueryBuilder\Tests\FunctionalTestCase;
use MakinaCorpus\QueryBuilder\Type\Type;

abstract class AbstractSchemaTestCase extends FunctionalTestCase
{
    /** @before */
    protected function createSchema(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        $bridge = $this->getBridge();

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
                $bridge->executeStatement('DROP TABLE ?::table', [$table]);
            } catch (DatabaseObjectDoesNotExistError) {}
        }

        switch ($bridge->getServerFlavor()) {

            case Platform::MARIADB:
            case Platform::MYSQL:
                $bridge->executeStatement(
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
                $bridge->executeStatement(
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
                $bridge->executeStatement(
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
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE no_pk_table (
                        id int UNIQUE NOT NULL,
                        name text DEFAULT NULL
                    )
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE renamed_table (
                        id int UNIQUE NOT NULL,
                        name text DEFAULT NULL
                    )
                    SQL
                );
                break;

            case Platform::SQLSERVER:
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE org (
                        id int UNIQUE NOT NULL,
                        dept nvarchar(255) NOT NULL,
                        role nvarchar(255) NOT NULL,
                        name nvarchar(max) DEFAULT NULL,
                        balance decimal(10,2) NOT NULL DEFAULT 0.0,
                        employes int NOT NULL DEFAULT 0,
                        PRIMARY KEY (dept, role)
                    )
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE users (
                        id int IDENTITY NOT NULL PRIMARY KEY,
                        org_id INT DEFAULT NULL,
                        name nvarchar(max) DEFAULT NULL,
                        username nvarchar(255) DEFAULT NULL,
                        email nvarchar(255) UNIQUE NOT NULL,
                        date datetime2 DEFAULT current_timestamp,
                        CONSTRAINT users_org_id FOREIGN KEY (org_id)
                            REFERENCES org (id)
                            ON DELETE CASCADE
                    )
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE user_address (
                        id int IDENTITY NOT NULL PRIMARY KEY,
                        org_id int DEFAULT NULL,
                        user_id int NOT NULL,
                        city nvarchar(max) NOT NULL,
                        country nvarchar(6) NOT NULL DEFAULT 'fr',
                        CONSTRAINT user_address_user_id_fk FOREIGN KEY (user_id)
                            REFERENCES users (id)
                            ON DELETE CASCADE
                    )
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE no_pk_table (
                        id int UNIQUE NOT NULL,
                        name ntext DEFAULT NULL
                    )
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE renamed_table (
                        id int UNIQUE NOT NULL,
                        name ntext DEFAULT NULL
                    )
                    SQL
                );
                break;

            case Platform::SQLITE:
                $bridge->executeStatement(
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
                $bridge->executeStatement(
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
                $bridge->executeStatement(
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
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE no_pk_table (
                        id int UNIQUE NOT NULL,
                        name text DEFAULT NULL
                    )
                    SQL
                );
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE renamed_table (
                        id int UNIQUE NOT NULL,
                        name text DEFAULT NULL
                    )
                    SQL
                );
                break;

            case Platform::POSTGRESQL:
                $bridge->executeStatement(
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
                $bridge->executeStatement(
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
                $bridge->executeStatement(
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
                $bridge->executeStatement(
                    <<<SQL
                    CREATE TABLE no_pk_table (
                        id int UNIQUE NOT NULL,
                        name text DEFAULT NULL
                    )
                    SQL
                );
                $bridge->executeStatement(
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

    protected function getTestingCollation(): string
    {
        if ($this->ifDatabase(Platform::MYSQL)) {
            return 'utf8_general_ci';
        }
        if ($this->ifDatabase(Platform::POSTGRESQL)) {
            // Arbitrary taken from: "SELECT collname FROM pg_collation" and
            // existing in all tested containers.
            return 'fr-FR-x-icu';
        }
        if ($this->ifDatabase(Platform::SQLSERVER)) {
            return 'Latin1_General_100_CI_AS_KS_SC_UTF8';
        }
        return 'utf8';
    }

    public function testCallbackChange(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->query(
                    fn (QueryBuilder $queryBuilder) => $queryBuilder
                        ->select()
                        ->columnRaw('1')
                        ->executeStatement()
                )
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testCallbackCondition(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->ifCallback(
                    fn (QueryBuilder $queryBuilder) => (bool) $queryBuilder
                        ->select()
                        ->columnRaw('1')
                        ->executeQuery()
                        ->fetchOne()
                )
                    ->addColumn('org', 'if_callback_added', 'text', true)
                ->endIf()
                ->ifCallback(
                    fn (QueryBuilder $queryBuilder) => (bool) $queryBuilder
                        ->select()
                        ->columnRaw('null')
                        ->executeQuery()
                        ->fetchOne()
                )
                    ->addColumn('org', 'if_callback_added_not', 'text', true)
                ->endIf()
            ->commit()
        ;

        $columnNames = $this
            ->getSchemaManager()
            ->getTable('test_db', 'org')
            ->getColumnNames()
        ;

        self::assertContains('if_callback_added', $columnNames);
        self::assertNotContains('if_callback_added_not', $columnNames);
    }

    public function testColumnAddNullable(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
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
        self::assertSameType('int', $column->getValueType());
    }

    public function testColumnAddNullableWithDefault(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
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
        self::assertSameType('int', $column->getValueType());
    }

    public function testColumnAddNotNull(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
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
        self::assertSameType('int', $column->getValueType());
    }

    public function testColumnAddNotNullWithDefault(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
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
        self::assertSameType('int', $column->getValueType());
    }

    public function testColumnModifyType(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);
        $this->skipIfDatabase(Platform::SQLSERVER, 'SQL Server cannot change type when there is a default, see testColumnModifyTypeAndDefault().');

        $this
            ->getSchemaManager()
            ->modify()
                ->modifyColumn(
                    table: 'user_address',
                    name: 'country',
                    type: 'text',
                )
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
            ->getColumn('country')
        ;

        // This changed.
        self::assertSameType(Type::text(), $column->getValueType());

        // This didn't.
        self::assertFalse($column->isNullable());
        //self::assertSame("'fr'", $column->getDefault()); // @todo
        //self::assertNotSame('C', $column->getCollation()); // @todo
    }

    public function testColumnModifyTypeAndDefault(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        $this
            ->getSchemaManager()
            ->modify()
                ->modifyColumn(
                    table: 'user_address',
                    name: 'country',
                    type: 'text',
                    default: "'fr'",
                )
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
            ->getColumn('country')
        ;

        // This changed.
        self::assertSameType(Type::text(), $column->getValueType());

        // This didn't.
        self::assertFalse($column->isNullable());
        //self::assertSame("'fr'", $column->getDefault()); // @todo
        //self::assertNotSame('C', $column->getCollation()); // @todo
    }

    public function testColumnModifyCollation(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        $collation = $this->getTestingCollation();

        $this
            ->getSchemaManager()
            ->modify()
                ->modifyColumn(
                    table: 'user_address',
                    name: 'country',
                    collation: $collation,
                    type: 'text'
                )
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
            ->getColumn('country')
        ;

        // This changed.
        // self::assertSame($collation, $column->getCollation()); // @todo

        // This didn't.
        self::assertFalse($column->isNullable());
        //self::assertSame("'fr'", $column->getDefault()); // @todo
        self::assertSameType('text', $column->getValueType());
    }

    public function testColumnModifyDropDefault(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        $this
            ->getSchemaManager()
            ->modify()
                ->modifyColumn(
                    table: 'user_address',
                    name: 'country',
                    dropDefault: true,
                )
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
            ->getColumn('country')
        ;

        // This changed.
        //self::assertNull($column->getDefault()); // @todo

        // This didn't.
        self::assertFalse($column->isNullable());
        self::assertSameType(Type::varchar(6), $column->getValueType());
        // self::assertNotSame('C', $column->getCollation()); // @todo
    }

    public function testColumnModifyDefault(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        $this
            ->getSchemaManager()
            ->modify()
                ->modifyColumn(
                    table: 'user_address',
                    name: 'country',
                    default: "'en'",
                )
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
            ->getColumn('country')
        ;

        // This changed.
        //self::assertSame("'en'", $column->getDefault()); // @todo

        // This didn't.
        self::assertFalse($column->isNullable());
        self::assertSameType('varchar(6)', $column->getValueType());
        // self::assertNotSame('C', $column->getCollation()); // @todo
    }

    public function testColumnModifyNullable(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        $this
            ->getSchemaManager()
            ->modify()
                ->modifyColumn(
                    table: 'user_address',
                    name: 'country',
                    nullable: true,
                )
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
            ->getColumn('country')
        ;

        // This changed.
        self::assertTrue($column->isNullable());

        // This didn't.
        self::assertSameType(Type::varchar(6), $column->getValueType());
        //self::assertSame("'en'", $column->getDefault()); // @todo
        // self::assertNotSame('C', $column->getCollation()); // @todo
    }

    public function testColumnModifyEverything(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        $collation = $this->getTestingCollation();

        $this
            ->getSchemaManager()
            ->modify()
                ->modifyColumn(
                    table: 'user_address',
                    name: 'country',
                    nullable: true,
                    type: 'varchar(24)',
                    default: "'es'",
                    collation: $collation,
                )
            ->commit()
        ;

        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
            ->getColumn('country')
        ;

        self::assertTrue($column->isNullable());
        self::assertSameType('varchar(24)', $column->getValueType());
        //self::assertSame("'es'", $column->getDefault()); // @todo
        // self::assertSame($collation, $column->getCollation()); // @todo
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
            ->modify()
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
        $this->skipIfDatabase(Platform::SQLITE);

        $this
            ->getSchemaManager()
            ->modify()
                ->addForeignKey('user_address', ['org_id'], 'org', ['id'])
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testForeignKeyModify(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        self::markTestIncomplete();
        self::expectNotToPerformAssertions();
    }

    public function testForeignKeyDrop(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        $this
            ->getSchemaManager()
            ->modify()
                ->addForeignKey('user_address', ['org_id'], 'org', ['id'], 'user_address_org_org_id_fk')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify()
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
            ->modify()
                ->createIndex('users', ['email'], 'users_email_idx')
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testIndexDrop(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->createIndex('users', ['email'], 'users_email_idx')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify()
                ->dropIndex('users', 'users_email_idx')
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testIndexRename(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->createIndex('no_pk_table', ['id'], 'my_foo_unique_index')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify()
                ->dropIndex('no_pk_table', 'my_foo_unique_index')
            ->commit()
        ;

        // @todo Implement indexes read in schema manager.
        self::expectNotToPerformAssertions();
    }

    public function testPrimaryKeyAdd(): void
    {
        $this->skipIfDatabase(Platform::SQLITE);

        $this
            ->getSchemaManager()
            ->modify()
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
        $this->skipIfDatabase(Platform::SQLITE);

        $this
            ->getSchemaManager()
            ->modify()
                ->addPrimaryKey('no_pk_table', ['id'], 'no_pk_table_pkey_with_name')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify()
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
            ->modify()
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
        self::assertSameType('text', $column->getValueType());
    }

    public function testTableCreateTemporary(): void
    {
        self::markTestIncomplete("PostgreSQL refuses to create a temporary relation in a non-temporary schema ?!");

        $this
            ->getSchemaManager()
            ->modify()
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
            ->modify()
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
            ->modify()
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
            ->modify()
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
            ->modify()
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
            ->modify()
                ->dropTable('user_address')
            ->commit()
        ;

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches("/Table 'test_db\..*\.user_address' does not exist/");
        $this
            ->getSchemaManager()
            ->getTable('test_db', 'user_address')
        ;
    }

    public function testTableRename(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->renameTable('renamed_table', 'renamed_table_new_name')
            ->commit()
        ;

        $table = $this
            ->getSchemaManager()
            ->getTable('test_db', 'renamed_table_new_name')
        ;

        self::assertSame('renamed_table_new_name', $table->getName());

        self::expectException(QueryBuilderError::class);
        self::expectExceptionMessageMatches("/Table 'test_db\..*\.renamed_table' does not exist/");
        $this
            ->getSchemaManager()
            ->getTable('test_db', 'renamed_table')
        ;
    }

    public function testUniqueKeyAdd(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->addUniqueKey('users', ['username'])
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testUniqueKeyAddNamed(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->addUniqueKey('users', ['username'], 'users_unique_username_idx')
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testUniqueKeyDrop(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->addUniqueKey('users', ['username'], 'users_unique_username_idx')
            ->commit()
        ;

        $this
            ->getSchemaManager()
            ->modify()
                ->dropUniqueKey('users', 'users_unique_username_idx')
            ->commit()
        ;

        self::expectNotToPerformAssertions();
    }

    public function testIfTableExists(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->ifTableExists('org')
                    ->addColumn('org', 'if_added_col', 'text', true)
                ->endIf()
                ->ifTableNotExists('org')
                    ->addColumn('org', 'if_not_added_col', 'text', true)
                ->endIf()
            ->commit()
        ;

        $columnNames = $this
            ->getSchemaManager()
            ->getTable('test_db', 'org')
            ->getColumnNames()
        ;

        self::assertContains('if_added_col', $columnNames);
        self::assertNotContains('if_not_added_col', $columnNames);
    }

    public function testIfColumnExists(): void
    {
        $this
            ->getSchemaManager()
            ->modify()
                ->ifColumnExists('org', 'role')
                    ->addColumn('org', 'if_added_col_2', 'text', true)
                ->endIf()
                ->ifColumnNotExists('org', 'role')
                    ->addColumn('org', 'if_not_added_col_2', 'text', true)
                ->endIf()
                ->ifColumnExists('org', 'role_nope')
                    ->addColumn('org', 'if_added_col_3', 'text', true)
                ->endIf()
                ->ifColumnNotExists('org', 'role_nope')
                    ->addColumn('org', 'if_not_added_col_3', 'text', true)
                ->endIf()
            ->commit()
        ;

        $columnNames = $this
            ->getSchemaManager()
            ->getTable('test_db', 'org')
            ->getColumnNames()
        ;

        self::assertContains('if_added_col_2', $columnNames);
        self::assertNotContains('if_not_added_col_2', $columnNames);
        self::assertNotContains('if_added_col_3', $columnNames);
        self::assertContains('if_not_added_col_3', $columnNames);
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
        self::assertSameType(Type::decimal(10, 2), $column->getValueType());
        self::assertSame('org', $column->getTable());
        self::assertSame($this->getSchemaManager()->getDefaultSchema(), $column->getSchema());
        self::assertMatchesRegularExpression('/^column:test_db\..*\.org.balance$/', $column->toString());
        self::assertFalse($column->isNullable());
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

        self::assertTrue($column->getValueType()->unsigned);
    }

    public function testColumnText(): void
    {
        $column = $this
            ->getSchemaManager()
            ->getTable('test_db', 'org')
            ->getColumn('name')
        ;

        self::assertNotEmpty($column->getCollation());
        self::assertSameType(Type::text(), $column->getValueType());
        self::assertSame('org', $column->getTable());
        self::assertSame($this->getSchemaManager()->getDefaultSchema(), $column->getSchema());
        self::assertMatchesRegularExpression('/^column:test_db\..*\.org.name$/', $column->toString());
        self::assertTrue($column->isNullable());
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

        if ($this->ifDatabaseNot(Platform::SQLITE)) {
            self::assertCount(1, $table->getReverseForeignKeys());
        }
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

        if ($this->ifDatabaseNot(Platform::SQLITE)) {
            self::assertNotNull($reverseForeignKey = ($table->getReverseForeignKeys()[0] ?? null));
            self::assertSame(['user_id'], $reverseForeignKey->getColumnNames());
            self::assertSame(['id'], $reverseForeignKey->getForeignColumnNames());
            self::assertSame('user_address', $reverseForeignKey->getTable());
            self::assertSame('users', $reverseForeignKey->getForeignTable());
        }
    }

    public function testTableReverseForeignKeys(): void
    {
        self::markTestIncomplete();
    }
}
