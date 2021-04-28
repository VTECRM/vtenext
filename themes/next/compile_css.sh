#! /bin/sh

# crmv@82419

SASSDIR="./scss/"
OUTDIR="./"
#SASSOPTS="-C --scss --sourcemap=none -t compressed"
SASSOPTS="--scss --sourcemap=none -t compressed"

# check sass
SASSVER=$(sass -v 2>/dev/null)
if [ -z "$SASSVER" ]; then
	echo "Sass not installed, please refer to http://sass-lang.com/install for the installation guide."
	exit 1
fi

# Install on Debian/Ubuntu:
#
# as root:
# apt-get install rubygems
# gem install sass
# [optional] gem install compass
#

echo "Compiling..."
for f in "$SASSDIR"/*.scss; do
	BNAME=$(basename $f)
	OUTFILE=$(basename "$f" ".scss")".css"
	OUTFILETMP="$OUTFILE"".new"
	BEGIN=$(echo $BNAME | cut -b 1)
	if [ "$BEGIN" != '_' ]; then
		echo $BNAME
		# use a temporary file so I don't have the real css changed with until it's done!
		sass $SASSOPTS "$f" "$OUTDIR""$OUTFILETMP" && mv -f "$OUTFILETMP" "$OUTFILE"
	fi
done
echo "Done."