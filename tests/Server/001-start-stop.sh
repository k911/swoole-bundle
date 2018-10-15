#!/usr/bin/env bash

cd "$( dirname "${BASH_SOURCE[0]}" )/../Fixtures/Symfony/app";

HOST=localhost
PORT=9999
CURL_REQUEST=http://${HOST}:${PORT}
EXIT_CODE=0

./console swoole:server:start --port ${PORT} --ansi

echo "[Info] Executing curl request: $CURL_REQUEST";
RESULT=$(curl ${CURL_REQUEST} -s)
echo "[Info] Result: $RESULT";
if [[ "$RESULT" = '{"hello":"world!"}' ]]; then
    echo "[Test] OK";
else
    echo "[Test] FAIL";
    EXIT_CODE=1;
fi

./console swoole:server:stop --ansi

exit ${EXIT_CODE};
