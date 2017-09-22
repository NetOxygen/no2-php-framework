PHPUNITDIR=testing
PHPUNITOPTIONS=--verbose --bootstrap bootstrap.inc.php
PHPUNITDBNAME=no2test
PHPUNITDBUSER=no2test
PHPUNITDBPASS=no2test

.PHONY: phpunit phpunit-help

define set-database
	@echo ">>> Setting test data"
	cd ${PHPUNITDIR} && cat db/* | mysql -u${PHPUNITDBUSER} -p${PHPUNITDBPASS} ${PHPUNITDBNAME};
endef

phpunit-no2:
	$(set-database)
	@echo ">>> Running framework unit tests"
	cd ${PHPUNITDIR} && ./bin/phpunit ${PHPUNITOPTIONS} no2.unit.tests
phpunit-project:
	$(set-database)
	@echo ">>> Running project unit tests"
	cd ${PHPUNITDIR} && ./bin/phpunit ${PHPUNITOPTIONS} unit.tests
phpunit: phpunit-no2 phpunit-project
test: phpunit

phpunit-help:
	@echo "- phpunit (< test): run PHPUnit to perform unit tests."
help: phpunit-help
