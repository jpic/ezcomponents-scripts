#!/bin/bash

cd packages

if test -d autoload; then
	echo "Autoload directory exists."
else
	echo "Creating missing 'autoload' directory."
	mkdir autoload
fi

for i in `find . -name \*_autoload.php | grep -v tutorial_autoload.php`; do
	p=`echo $i | cut -d / -f 2`;
	r=`echo $i | cut -d / -f 3`;

	if test ! $p == "autoload"; then
		if test ! $r == "releases"; then
			b=`echo $i | cut -d / -f 5`
			if test -L autoload/$b; then
				echo "Symlink for $b to $i exists."
			else
				echo "Creating symlink from $i to autoload/$b."
				ln -s "../$i" "autoload/$b"
			fi
		fi
	fi
done
