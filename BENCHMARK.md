# Benchmark

Benchmarks uses `phpbench/phpbench` library.

## Usage

Arbitrarily run benchmarks:

```sh
./vendor/bin/phpbench run tests/Benchmark --report=aggregate
```

Run and tag benchmark (for later comparison):

```sh
./vendor/bin/phpbench run tests/Benchmark --report=aggregate --tag=v30
```

Run benchmark and compare with tag:

```sh
./vendor/bin/phpbench run tests/Benchmark --report=aggregate --ref=v30
```

## Preliminary results

### 1.1 results

_Testing conditions: AMD Ryzen 7 PRO 6850U, PHP 8.3.4_

```
+-----------------+------------------------------+-----+------+-----+----------+-----------+--------+
| benchmark       | subject                      | set | revs | its | mem_peak | mode      | rstdev |
+-----------------+------------------------------+-----+------+-----+----------+-----------+--------+
| WriterBench     | benchArbitrary               |     | 1000 | 5   | 2.379mb  | 999.157μs | ±2.81% |
| ConversionBench | benchIntFromSql              |     | 3000 | 5   | 1.976mb  | 52.563μs  | ±0.32% |
| ConversionBench | benchIntToSql                |     | 3000 | 5   | 1.976mb  | 145.708μs | ±0.52% |
| ConversionBench | benchIntToSqlNullType        |     | 3000 | 5   | 1.976mb  | 143.360μs | ±0.38% |
| ConversionBench | benchIntToSqlWithType        |     | 3000 | 5   | 1.976mb  | 101.565μs | ±1.62% |
| ConversionBench | benchRamseyUuidFromSql       |     | 3000 | 5   | 1.976mb  | 40.229μs  | ±0.38% |
| ConversionBench | benchRamseyUuidToSql         |     | 3000 | 5   | 1.976mb  | 85.923μs  | ±0.25% |
| ConversionBench | benchRamseyUuidToSqlNullType |     | 3000 | 5   | 1.975mb  | 94.053μs  | ±0.40% |
| ConversionBench | benchRamseyUuidToSqlWithType |     | 3000 | 5   | 1.976mb  | 44.682μs  | ±1.15% |
| ConversionBench | benchArrayFromSql            |     | 3000 | 5   | 2.004mb  | 305.592μs | ±0.66% |
| ConversionBench | benchArrayToSql              |     | 3000 | 5   | 2.003mb  | 378.250μs | ±0.77% |
| ConversionBench | benchArrayToSqlNullType      |     | 3000 | 5   | 2.003mb  | 383.562μs | ±0.97% |
| ConversionBench | benchArrayToSqlWithType      |     | 3000 | 5   | 2.003mb  | 336.824μs | ±1.45% |
+-----------------+------------------------------+-----+------+-----+----------+-----------+--------+
```

Analysis: added the type API, induces some mandatory slowness in value conversion. Some deviations
might be corrected later by improving the converter.


### 1.0 results

_Testing conditions: AMD Ryzen 7 PRO 6850U, PHP 8.3.4_

```
+-----------------+------------------------------+-----+------+-----+----------+-----------+--------+
| benchmark       | subject                      | set | revs | its | mem_peak | mode      | rstdev |
+-----------------+------------------------------+-----+------+-----+----------+-----------+--------+
| WriterBench     | benchArbitrary               |     | 1000 | 5   | 2.338mb  | 1.002ms   | ±0.60% |
| ConversionBench | benchIntFromSql              |     | 3000 | 5   | 1.964mb  | 49.645μs  | ±2.89% |
| ConversionBench | benchIntToSql                |     | 3000 | 5   | 1.964mb  | 53.701μs  | ±0.92% |
| ConversionBench | benchIntToSqlNullType        |     | 3000 | 5   | 1.964mb  | 55.940μs  | ±2.76% |
| ConversionBench | benchRamseyUuidFromSql       |     | 3000 | 5   | 1.964mb  | 36.084μs  | ±3.07% |
| ConversionBench | benchRamseyUuidToSql         |     | 3000 | 5   | 1.964mb  | 37.128μs  | ±0.45% |
| ConversionBench | benchRamseyUuidToSqlNullType |     | 3000 | 5   | 1.964mb  | 64.340μs  | ±0.22% |
| ConversionBench | benchArrayFromSql            |     | 3000 | 5   | 1.964mb  | 298.337μs | ±0.77% |
| ConversionBench | benchArrayToSql              |     | 3000 | 5   | 1.964mb  | 177.520μs | ±1.39% |
| ConversionBench | benchArrayToSqlNullType      |     | 3000 | 5   | 1.964mb  | 189.586μs | ±0.48% |
+-----------------+------------------------------+-----+------+-----+----------+-----------+--------+
```
