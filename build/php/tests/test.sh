#!/bin/bash

export PHP_BINARIES="$2"
chmod +x "$PHP_BINARIES"
DIR="$(cd -P "$( dirname "${BASH_SOURCE[0]}" )" && pwd)"
export TEST_DIR="$DIR/$1/"

TEST_NUMBER=("$TEST_DIR"*)
TEST_NUMBER=${#TEST_NUMBER[@]}

set +e

INCREMENT=0
FAILED=0

cd "$TEST_DIR"
echo "Doing tests on $TEST_DIR"

for f in *; do
	INCREMENT=$((INCREMENT+1))
	echo -n "[$INCREMENT/$TEST_NUMBER] $f ... "
	chmod +x "$f"
	"./$f"
	STATUS=$?
	if [ $STATUS != 0 ]; then
		echo "FAILED!"
		FAILED=$((FAILED+1))
	else
		echo "OK"
	fi
done

echo "Ran $INCREMENT tests, $FAILED failed."

exit 0