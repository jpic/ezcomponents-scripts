#!/bin/sh

for i in `find . -name \*.php`; do perl -p -i -e "s/^\s\*\s\@copyright(.*)/ * \@copyright Copyright (C) 2005-2008 eZ systems as. All rights reserved./g" $i; done
