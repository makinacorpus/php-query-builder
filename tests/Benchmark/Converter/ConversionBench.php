<?php

declare(strict_types=1);

namespace Goat\Benchmark\Converter;

use MakinaCorpus\QueryBuilder\Converter\Converter;
use MakinaCorpus\QueryBuilder\Type\Type;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Groups;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[BeforeMethods(["setUp"])]
final class ConversionBench
{
    private Converter $converter;

    private array $arrayData;
    private string $arrayDataAsString;
    private Type $arrayType;
    private Type $intType;
    private UuidInterface $ramseyUuidData;
    private string $ramseyUuidDataAsString;
    private Type $ramseyUuidType;

    public function setUp(): void
    {
        $this->converter = new Converter();

        $this->arrayData = ["foo", "bar", "l''och"];
        $this->arrayDataAsString = "{foo,bar,l''och}";
        $this->arrayType = Type::varchar()->toArray();
        $this->intType = Type::int();
        $this->ramseyUuidData = Uuid::fromString('a9336bfe-1a3b-4d14-a2da-38b819da0e96');
        $this->ramseyUuidDataAsString = 'a9336bfe-1a3b-4d14-a2da-38b819da0e96';
        $this->ramseyUuidType = Type::uuid();
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "output", "int"])]
    public function benchIntFromSql(): void
    {
        $this->converter->fromSQL('int', '152485788');
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "input", "int"])]
    public function benchIntToSql(): void
    {
        $this->converter->toSQL(152485788, 'int8');
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "input", "int"])]
    public function benchIntToSqlNullType(): void
    {
        $this->converter->toSQL(152485788, null);
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "input", "int"])]
    public function benchIntToSqlWithType(): void
    {
        $this->converter->toSQL(152485788, $this->intType);
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "output", "uuid"])]
    public function benchRamseyUuidFromSql(): void
    {
        $this->converter->fromSQL(UuidInterface::class, $this->ramseyUuidDataAsString);
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "input", "uuid"])]
    public function benchRamseyUuidToSql(): void
    {
        $this->converter->toSQL($this->ramseyUuidData, 'uuid');
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "input", "uuid"])]
    public function benchRamseyUuidToSqlNullType(): void
    {
        $this->converter->toSQL($this->ramseyUuidData, null);
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "input", "uuid"])]
    public function benchRamseyUuidToSqlWithType(): void
    {
        $this->converter->toSQL($this->ramseyUuidData, $this->ramseyUuidType);
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "output", "array"])]
    public function benchArrayFromSql(): void
    {
        $this->converter->fromSQL('string[]', $this->arrayDataAsString);
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "input", "array"])]
    public function benchArrayToSql(): void
    {
        $this->converter->toSQL($this->arrayData, 'varchar[]');
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "input", "array"])]
    public function benchArrayToSqlNullType(): void
    {
        $this->converter->toSQL($this->arrayData, null);
    }

    #[Revs(3000)]
    #[Iterations(5)]
    #[Groups(["converter", "input", "array"])]
    public function benchArrayToSqlWithType(): void
    {
        $this->converter->toSQL($this->arrayData, $this->arrayType);
    }
}
