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
| WriterBench     | benchQuerySimple             |     | 1000 | 5   | 2.498mb  | 310.188μs | ±0,88% |
| WriterBench     | benchQueryWithJoin           |     | 1000 | 5   | 2.501mb  | 468.132μs | ±0,27% |
| WriterBench     | benchQueryBig                |     | 1000 | 5   | 2.499mb  | 879.123μs | ±0,42% |
| ConversionBench | benchIntFromSql              |     | 3000 | 5   | 1.994mb  | 52.838μs  | ±0,66% |
| ConversionBench | benchIntToSql                |     | 3000 | 5   | 1.964mb  | 45.028μs  | ±0,34% |
| ConversionBench | benchIntToSqlNullType        |     | 3000 | 5   | 1.964mb  | 26.221μs  | ±0,75% |
| ConversionBench | benchIntToSqlWithType        |     | 3000 | 5   | 1.964mb  | 14.827μs  | ±0,45% |
| ConversionBench | benchRamseyUuidFromSql       |     | 3000 | 5   | 1.994mb  | 39.673μs  | ±0,59% |
| ConversionBench | benchRamseyUuidToSql         |     | 3000 | 5   | 1.994mb  | 71.404μs  | ±0,44% |
| ConversionBench | benchRamseyUuidToSqlNullType |     | 3000 | 5   | 1.993mb  | 83.386μs  | ±0,30% |
| ConversionBench | benchRamseyUuidToSqlWithType |     | 3000 | 5   | 1.994mb  | 41.074μs  | ±0,87% |
| ConversionBench | benchArrayFromSql            |     | 3000 | 5   | 2.022mb  | 304.246μs | ±0,21% |
| ConversionBench | benchArrayToSql              |     | 3000 | 5   | 1.964mb  | 98.314μs  | ±0,90% |
| ConversionBench | benchArrayToSqlNullType      |     | 3000 | 5   | 1.964mb  | 96.717μs  | ±0,46% |
| ConversionBench | benchArrayToSqlWithType      |     | 3000 | 5   | 1.964mb  | 67.945μs  | ±0,31% |
+-----------------+------------------------------+-----+------+-----+----------+-----------+--------+
```

Analysis: adding the type API induces some mandatory slowness in value conversion. Some deviations
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
