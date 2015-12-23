DOCDIR=doxygen

.PHONY: doxygen doxygen-clean

doxygen:
	cd ${DOCDIR} && doxygen doxygen.conf
doc: doxygen

doxygen-clean:
	-rm -rf ${DOCDIR}/html
clean: doxygen-clean

doxygen-help:
	@echo "- doxgen (< doc): run doxygen to generate project's documentation."
help: doxygen-help
