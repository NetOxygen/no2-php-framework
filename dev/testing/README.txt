This directory contains all the testing stuff.

config/
    Contains the test config file.
    It differs by the project config by:
        - defining a different database (no2test)
        - setting a different log file (test.log in testing directory)

db/
    Contains the test database initialization scripts, which
    create three tables (users, roles and users_roles) and
    fill them with some test data.
    They are automatically runned before the framework unit tests.

features/
    Contains Behat features

no2.unit.tests/
    Contains the framework unit tests

selenium2/
    Should contain the selenium stuff (JAR, ...)

unit.tests
    This is here that you, the app developper, should put your unit tests

behat.yml
    The Behat configuration file

bootstrap.inc.php
    The framework bootstrap file tuned for the testing environment.
    It differs from the PROJECTDIR/bootstrap.inc.php by:
        - defining the TESTSDIR constant
        - loading the tests models instead of the app ones

composer.*
    Composer stuff

README.txt
    This file

First thing to do:
    cd [TESTSDIR] && ./composer.phar install

To setup Behat:
    - See http://docs.behat.org/cookbook/behat_and_mink.html
    - From PROJECTDIR/dev run "make behat"

To run the framework unit tests
    - Create the no2test database
        - mysql> CREATE DATABASE no2test;
        - mysql> GRANT ALL PRIVILEGES ON no2test.* TO no2test@localhost IDENTIFIED
                 BY "no2test";
    - From PROJECTDIR/dev run "make phpunit-no2"
