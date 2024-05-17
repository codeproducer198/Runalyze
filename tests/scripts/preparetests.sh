#!/bin/bash

# prepare the created test database with the test schema

set -e

# set your host where the db is running; example 127.0.0.1
DB_HOST="-h mysql"
DB_USER="-urunalyze_test"

# base is https://github.com/Runalyze/Runalyze/blob/support/4.3.x/.travis.yml

apt-get update
apt-get install --no-install-recommends mariadb-client tzdata

php bin/console --env=test doctrine:schema:update --force --complete

mysql $DB_USER $DB_HOST runalyze_unittest < inc/install/structure.sql

