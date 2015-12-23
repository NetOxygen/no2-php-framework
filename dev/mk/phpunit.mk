PHPUNITDIR=testing
TEST_DB_NAME=no2test
TEST_DB_USER=no2test
TEST_DB_PASS=no2test

.PHONY: phpunit phpunit-help

define set-database
	@echo ">>> Setting test data"
	cd ${PHPUNITDIR} && cat db/* | mysql -u${TEST_DB_USER} -p${TEST_DB_PASS} ${TEST_DB_NAME};
endef

phpunit-no2:
	$(set-database)
	@echo ">>> Running framework unit tests"
	cd ${PHPUNITDIR} && ./bin/phpunit --bootstrap bootstrap.inc.php no2.unit.tests
phpunit-project:
	$(set-database)
	@echo ">>> Running project unit tests"
	cd ${PHPUNITDIR} && ./bin/phpunit unit.tests
phpunit: phpunit-no2 phpunit-project
test: phpunit

phpunit-help:
	@echo "- phpunit (< test): run PHPUnit to perform unit tests."
help: phpunit-help
