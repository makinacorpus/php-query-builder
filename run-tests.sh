# IGNORE THIS FILE
#
# Older script kept because sometime, I want to run things
# manually, I simply copy/paste those stuff.
#
exit;

echo "Running tests with MySQL 8"
DBAL_DRIVER=pdo_mysql \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=password \
    DBAL_PORT=9502 \
    DBAL_ROOT_PASSWORD="password" \
    DBAL_ROOT_USER="root" \
    DBAL_USER=root \
    vendor/bin/phpunit

echo "Running tests with MariaDB 11"
DBAL_DRIVER=pdo_mysql \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=password \
    DBAL_PORT=9503 \
    DBAL_ROOT_PASSWORD="password" \
    DBAL_ROOT_USER="root" \
    DBAL_USER=root \
    vendor/bin/phpunit

echo "Running tests with PostgreSQL 10"
DBAL_DRIVER=pdo_pgsql \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=password \
    DBAL_PORT=9504 \
    DBAL_ROOT_PASSWORD="password" \
    DBAL_ROOT_USER="postgres" \
    DBAL_USER=postgres \
    vendor/bin/phpunit

echo "Running tests with PostgreSQL 16"
DBAL_DRIVER=pdo_pgsql \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=password \
    DBAL_PORT=9505 \
    DBAL_ROOT_PASSWORD="password" \
    DBAL_ROOT_USER="postgres" \
    DBAL_USER=postgres \
    vendor/bin/phpunit

# echo "Running tests with Oracle 23"
# DBAL_DRIVER=pdo_oci \
#     DBAL_DBNAME=test_db \
#     DBAL_HOST=127.0.0.1 \
#     DBAL_PASSWORD=password \
#     DBAL_PORT=9606 \
#     DBAL_ROOT_PASSWORD="password" \
#     DBAL_ROOT_USER="oracle" \
#     DBAL_USER=oracle \
#     vendor/bin/phpunit

echo "Running tests with SQLServer 2019"
DBAL_DRIVER=pdo_sqlsrv \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    DBAL_PASSWORD=P@ssword123 \
    DBAL_PORT=9506 \
    DBAL_ROOT_PASSWORD="P@ssword123" \
    DBAL_ROOT_USER="sa" \
    DBAL_USER=sa \
    vendor/bin/phpunit

echo "Running tests with SQLite"
DBAL_DRIVER=pdo_sqlite \
    DBAL_DBNAME=test_db \
    DBAL_HOST=127.0.0.1 \
    vendor/bin/phpunit
