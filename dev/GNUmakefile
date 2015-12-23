# clean and all are just label where depedencies will be added by specific
# makefiles in the mk directory.
APPDIR=../app

.PHONY: clean all doc test help


help: pre-help
pre-help:
	@echo "===> virtual targets"
	@echo "- help:  show this help (default)."
	@echo "- all:   execute every targets."
	@echo "- test:  execute all testing targets."
	@echo "- doc:   execute all documentation targets."
	@echo "- clean: execute every clean task."
	@echo "===> targets"

all: doc test
	@echo all done.

clean:
	@echo clean.

include mk/*.mk
