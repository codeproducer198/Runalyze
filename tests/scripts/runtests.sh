#!/bin/bash

# Timezone Europe/Moscow is used in the tests - check it

if find /usr/share/zoneinfo -type f -or -type l | grep 'Europe/Moscow'; then
    echo "Timezone 'Europe/Moscow' available - continue"
else
    echo "Install timezones with 'apt-get update && apt-get install tzdata'"
    exit 1
fi

# "vendor/bin/phpunit" not executable because the x permission is not set when the container is build from the zip file, so use the call via php 
# PHPUNIT=vendor/bin/phpunit
PHPUNIT="php vendor/phpunit/phpunit/phpunit"

# based on the combinations of https://github.com/Runalyze/Runalyze/blob/support/4.3.x/.travis.yml

# before_script:
cp data/config.yml data/config_notest.yml
cp app/config/default_config.yml data/config.yml

# tests
echo '----------------------------- import -----------------------------------------------'
$PHPUNIT --colors -c tests/config.xml --group "import" --exclude-group "dependsOnSRTM,dependsOnTimezoneDatabase"

#..E.FF..........................F......FEF.....................  63 / 109 ( 57%)
#........F....F.....................F...E..E...                  109 / 109 (100%)
#
#Time: 5.11 minutes, Memory: 110.00MB
#Tests: 109, Assertions: 5688, Errors: 4, Failures: 8.

echo '----------------------------- requiresKernel,requiresDoctrine,requiresClient -------'
$PHPUNIT --colors -c tests/config.xml --group "requiresKernel,requiresDoctrine,requiresClient" --exclude-group "dependsOnSRTM,dependsOnTimezoneDatabase"

#........................................................F........ 65 / 83 ( 78%)
#...............F..                                                83 / 83 (100%)
#
#Time: 42.38 seconds, Memory: 147.00MB
#Tests: 83, Assertions: 305, Failures: 2.

echo '----------------------------- default,dependsOn,requiresSqlite ---------------------'
$PHPUNIT --colors -c tests/config.xml --group "default,dependsOn,requiresSqlite" --exclude-group "dependsOnSRTM,dependsOnTimezoneDatabase,import,requiresKernel,requiresDoctrine,requiresClient"

#................................................F........F...   61 / 1139 (  5%)
#.............................................................  122 / 1139 ( 10%)
#....................................F........................  183 / 1139 ( 16%)
#.............................................................  244 / 1139 ( 21%)
#...........................................................F.  305 / 1139 ( 26%)
#FF...............E.....F.....................................  366 / 1139 ( 32%)
#.............................................................  427 / 1139 ( 37%)
#.............................................................  488 / 1139 ( 42%)
#...........F.................................................  549 / 1139 ( 48%)
#.............................................................  610 / 1139 ( 53%)
#.............................EEEE............................  671 / 1139 ( 58%)
#.............................................................  732 / 1139 ( 64%)
#.............................................................  793 / 1139 ( 69%)
#.............................................................  854 / 1139 ( 74%)
#............................................F................  915 / 1139 ( 80%)
#.............................................................  976 / 1139 ( 85%)
#....................................F........................ 1037 / 1139 ( 91%)
#............................................................. 1098 / 1139 ( 96%)
#.........................................                     1139 / 1139 (100%)
#
#Time: 10.93 seconds, Memory: 44.00MB
#Tests: 1139, Assertions: 5215, Errors: 5, Failures: 10.

# details of test-results see 'test_result.txt'

cp data/config_notest.yml data/config.yml
