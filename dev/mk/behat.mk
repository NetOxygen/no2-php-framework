BEHATDIR=testing

.PHONY: behat behat-help

behat:
	cd ${BEHATDIR} && ./bin/behat
test: behat

behat-help:
	@echo "- behat (< test): run behat to perform full Scenario testing."
help: behat-help
