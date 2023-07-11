FILES=Digital_Humanities_Quarterly.js Atlantic.js Google_Scholar.js SFGate.js Wall_Street_Journal.js LA_Times.js Washington_Monthly.js New_Yorker.js VoxEU.js
INSTALL_DIR=FIXME
%.js : %.js.in
	sed -e '/@framework@/{r framework.js' -e 'd;}' $< > $@

all: $(FILES)

clean:
	rm -f $(FILES)

install: all
	cp $(FILES) $(INSTALL_DIR)
