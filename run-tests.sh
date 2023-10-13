#!/bin/bash

# Use this to run functional tests with real database.
# You need docker on your box for it work.

function sleep_10() {
    echo -ne "Waiting 10 seconds "
    for i in `seq 1 10`; do
        sleep 1 && echo -ne '.';
    done
    echo ""
}

function sleep_5() {
    echo -ne "Waiting 5 seconds "
    for i in `seq 1 5`; do
        sleep 1 && echo -ne '.';
    done
    echo ""
}

# echo "Running tests with MariaDB 11"
DOCKER_ID_MYSQL=`docker run -d -p 9306:3306 --env MARIADB_ROOT_PASSWORD="password" mariadb:11`
sleep_10
docker exec -ti $DOCKER_ID_MYSQL mariadb -uroot -ppassword -e 'create database test_db;'
DBAL_DRIVER=pdo_mysql DBAL_HOST=127.0.0.1 DBAL_PORT=9306 DBAL_USER=root DBAL_PASSWORD=password DBAL_DBNAME=test_db vendor/bin/phpunit
docker stop $DOCKER_ID_MYSQL
docker rm --force $DOCKER_ID_MYSQL

echo "Running tests with PostgreSQL 16"
DOCKER_ID_PGSQL=`docker run -d -p 9432:5432 --env POSTGRES_PASSWORD="password" postgres:16`
sleep_5 
docker exec -ti -u postgres $DOCKER_ID_PGSQL psql -c 'create database test_db;'
DBAL_DRIVER=pdo_pgsql DBAL_HOST=127.0.0.1 DBAL_PORT=9432 DBAL_USER=postgres DBAL_PASSWORD=password DBAL_DBNAME=test_db vendor/bin/phpunit
docker stop $DOCKER_ID_PGSQL
docker rm --force $DOCKER_ID_PGSQL
