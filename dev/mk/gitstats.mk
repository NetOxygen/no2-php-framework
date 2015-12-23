STATSDIR=gitstats

.PHONY: gitstats gitstats-clean gitstats-help

gitstats:
	gitstats ${APPDIR} ${STATSDIR}
doc: gitstats

gitstats-clean:
	-rm -rf ${STATSDIR}/
clean: gitstats-clean

gitstats-help:
	@echo "- gitstats (< doc): Generate git statistics."
help: gitstats-help
