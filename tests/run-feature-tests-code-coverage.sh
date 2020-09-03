#!/usr/bin/env bash

EXIT_CODE=0
MAX_TRIES=5

TEST_NO=0
for f in ./tests/Feature/*.php; do
    ((TEST_NO++))
    echo "[Test $TEST_NO] $f";

    TEST_EXIT_CODE=0
    for ((TRY_NO=1; TRY_NO <= MAX_TRIES; TRY_NO++)); do
        echo "[Test $TEST_NO] Try $TRY_NO of $MAX_TRIES";

        vendor/bin/phpunit "$f" --coverage-php "cov/feature-tests-$TEST_NO.cov" --colors=always
        TEST_EXIT_CODE=$?

        # Make sure server is killed for next test
        PID=$(lsof -t -i :9999)
        if [[ "" != "$PID" ]]; then
            kill -9 "$PID" || true
            sleep 1;
        fi

        if [[ "$TEST_EXIT_CODE" = "0" ]]; then
            break;
        fi
        sleep 1;
    done

    if [[ "$TEST_EXIT_CODE" != "0" ]]; then
        EXIT_CODE=1;
    fi

done

exit ${EXIT_CODE}
