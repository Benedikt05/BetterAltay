#!/bin/bash

OUTPUT=$(readelf -d "$PHP_BINARIES/bin/php")

if [ "$OUTPUT" != "There is no dynamic section in this file." ]; then
	exit 1
fi

exit 0