<?php

declare (strict_types=1);

namespace MakinaCorpus\QueryBuilder\Tests;

use MakinaCorpus\QueryBuilder\Dsn;
use PHPUnit\Framework\TestCase;

class DsnTest extends TestCase
{
    public function testBasics(): void
    {
        $dsn = Dsn::fromString('pdo_mysql://foo:bar@somehost.com:1234/some_database?server=mysql-10.0.0');

        self::assertFalse($dsn->isFile());
        self::assertSame('pdo', $dsn->getDriver());
        self::assertSame('mysql', $dsn->getVendor());
        self::assertSame('foo', $dsn->getUser());
        self::assertSame('bar', $dsn->getPassword());
        self::assertSame(1234, $dsn->getPort());
        self::assertSame('somehost.com', $dsn->getHost());
        self::assertSame('some_database', $dsn->getDatabase());
        self::assertSame('mysql-10.0.0', $dsn->getOption('server'));
        self::assertNull($dsn->getOption('non_existing_option'));
    }

    public function testWithFilename(): void
    {
        $dsn = Dsn::fromString('sqlite:///some/path.db?server=sqlite-3');

        self::assertTrue($dsn->isFile());
        self::assertSame('sqlite', $dsn->getVendor());
        self::assertSame('any', $dsn->getDriver());
        self::assertSame('sqlite-3', $dsn->getOption('server'));
        self::assertSame('/some/path.db', $dsn->getHost());
        self::assertSame('/some/path.db', $dsn->getDatabase());
    }

    public function testUserPassAreDecoded(): void
    {
        $dsn = Dsn::fromString('pdo_mysql://some%20user:some%20password@somehost.com:1234/some_database');

        self::assertSame('some user', $dsn->getUser());
        self::assertSame('some password', $dsn->getPassword());
    }

    public function testNoDatabaseRaiseException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        Dsn::fromString('pdo_mysql://some%20user:some%20password@thirdpartyprovider.com');
    }

    public function testNoSchemeRaiseException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        Dsn::fromString('some%20user:some%20password@thirdpartyprovider.com/bla');
    }

    public function testNoHostRaiseException(): void
    {
        self::expectException(\InvalidArgumentException::class);
        Dsn::fromString('oauth://some%20user:some%20password@?foo=bar');
    }

    public function testToUrl(): void
    {
        $dsn = Dsn::fromString('pdo_mysql://foo:bar@somehost.com:1234/some_database?server=mysql-10.0.0&bla=bla');

        self::assertSame('pdo_mysql://foo:bar@somehost.com:1234/some_database?server=mysql-10.0.0', $dsn->toUrl(['bla']));
    }
}
