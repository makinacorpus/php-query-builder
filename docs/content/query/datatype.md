# Data types

:::warning
Whereas the supported type list is still valid, this page is mostly
outdated and will be replaced by the [value converter page](../converter/converter).
:::

## Rationale

Data types are purely informational: the user may or may not specify its
value types when adding parameters to the query.

It will be exposed later to the bridge that will execute the queries.

For rationalisation and consistency, it is advised that you stick the to
following data type matrix.

Every type has a unique identifier, for example ``int`` which reprensents an
integer, and a set of type aliases, for example ``int4`` refers to ``int``.

## Data type matrix

| Type      | Aliases  | PHP Native type             | SQL type        | Notes                                                     |
|-----------|----------|-----------------------------|-----------------|-----------------------------------------------------------|
| bigint    | int8     | int                         | bigint          | size (32 or 64 bits) depends on your arch                 |
| bigserial | serial8  | int                         | bigserial       | size (32 or 64 bits) depends on your arch                 |
| bool      | boolean  | bool                        | boolean         |                                                           |
| bytea     | blob     | string or resource          | bytea           | some drivers will give you a resource instead of a string |
| date      |          | \DateTimeInterface          | date            | PHP has no date type, timezone might cause you trouble    |
| decimal   | numeric  | float                       | decimal         |                                                           |
| float4    | real     | float                       | float4          | May loose precision                                       |
| float8    | real     | float                       | float8          |                                                           |
| int       | int4     | int                         | int             | size (32 or 64 bits) depends on your arch                 |
| interval  |          | \DateInterval               | interval        | you probably will need to deambiguate from time           |
| json      | jsonb    | array                       | json            | difference between json and jsonb is in storage           |
| serial    | serial4  | int                         | serial          | size (32 or 64 bits) depends on your arch                 |
| time      |          | \DateInterval               | time            | you probably will need to deambiguate from interval       |
| timestamp | datetime | \DateTimeInterface          | timestamp       |                                                           |
| ulid      |          | \Symfony\Component\Uid\Ulid | uuid            | you will need to install symfony/uid in order to use it   |
| uuid      |          | \Ramsey\Uuid\UuidInterface  | uuid            | you will need to install ramsey/uuid in order to use it   |
| uuid      |          | \Symfony\Component\Uid\Uuid | uuid            | you will need to install symfony/uid in order to use it   |
| varchar   | text     | string                      | varchar or text |                                                           |

## ARRAY

All types without exception can be manipulated as value-arrays. In order to cast
values as typed arrays, use the form ``TYPE[]``, for example: ``int[]``.

When you want to pass an array of values into your parameters, just pass the
value transparently:

```php
use MakinaCorpus\QueryBuilder\Writer\Writer;

assert($writer instanceof Writer);

$writer->prepare(
    <<<SQL
    insert into foo (my_array) values (?)",
    SQL,
    [
        [1, 2, 3],
    ]
);
```
