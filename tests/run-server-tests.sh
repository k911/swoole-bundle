#!/usr/bin/env bash

cd "$( dirname "${BASH_SOURCE[0]}" )";

EXIT_CODE=0

for f in ./Server/*.sh; do
    echo "[Test] $f";
    if [[ "$COVERAGE" == "1" ]]; then
        APP_ENV=cov SWOOLE_ALLOW_XDEBUG=1 bash "$f" -H || EXIT_CODE=1;
    else
        bash "$f" -H || EXIT_CODE=1;
    fi
done

exit $EXIT_CODE
