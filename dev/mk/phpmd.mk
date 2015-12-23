REPORTSDIR=phpmd

.PHONY: phpmd phpmd-clean phpmd-help

phpmd:
	-phpmd ${APPDIR}/models,${APPDIR}/controllers,${APPDIR}/help.inc.php,${APPDIR}/router.class.php html codesize,design,unusedcode > ${REPORTSDIR}/index.html
all: phpmd

phpmd-clean:
	-rm -rf ${REPORTSDIR}/index.html
clean: phpmd-clean

phpmd-help:
	@echo "- phpmd (< all): run the PHP Mess Detector against the whole project's codebase."
help: phpmd-help
