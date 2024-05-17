#!/bin/bash

# drops and creates the test databases & db users

set -e

DB_HOST="-h 127.0.0.1"
# use the host of the docker bridge
DB_NET="'172.50.0.%'"

# base is https://github.com/Runalyze/Runalyze/blob/support/4.3.x/.travis.yml

# create/update database
echo "Create testdatabase. Enter root password."

mysql -uroot -p $DB_HOST -e \
"DROP DATABASE IF EXISTS runalyze_unittest; DROP DATABASE IF EXISTS runalyze_test;
 SET @@global.sql_mode = TRADITIONAL; CREATE DATABASE runalyze_unittest; CREATE DATABASE runalyze_test;
 CREATE USER IF NOT EXISTS runalyze_test@$DB_NET; GRANT ALL PRIVILEGES ON runalyze_unittest.* TO runalyze_test@$DB_NET; GRANT ALL PRIVILEGES ON runalyze_test.* to runalyze_test@$DB_NET;"
