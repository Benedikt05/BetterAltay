#!/bin/bash

OUTPUT=$("$PHP_BINARIES" -r 'echo 1;')

if [ "$OUTPUT" != "1" ]; then
	exit 1
fi

exit 0