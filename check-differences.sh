#!/bin/sh

# This script compares trunk with the given released version.

# The script should be run from the root and called like:
# ./scripts/check-differences release/<component>/<version>

comp=`echo $1 | cut -d / -f 2`
version=`echo $1 | cut -d / -f 3`

diff -N -I @version -x .svn -ru releases/$comp/$version trunk/$comp | less
