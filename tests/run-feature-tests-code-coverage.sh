#!/usr/bin/env bash

EXIT_CODE=0

i=0
for f in ./tests/Feature/*.php; do
    ((i++))
    echo "[Test $i] $f";
    vendor/bin/phpunit "$f" --coverage-php "cov/feature-tests-$i.cov" --colors=always || EXIT_CODE=1;
    # Make sure server is killed for next test
    PID=$(lsof -i :9999 | grep php | awk '{print $2}')
    if [[ "" != "$PID" ]]; then
        kill -9 ${PID}
        EXIT_CODE=1
    fi
    sleep 1;
done

exit ${EXIT_CODE}
