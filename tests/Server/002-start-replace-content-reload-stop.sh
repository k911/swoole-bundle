#!/usr/bin/env bash

cd "$( dirname "${BASH_SOURCE[0]}" )/../Fixtures/Symfony/app";

HOST=localhost
PORT=9999
CURL_REQUEST=http://${HOST}:${PORT}/test/replaced/content
ORIGINAL_TEXT="Wrong response!";
RESPONSE_TEXT_1="Hello world!";
RESPONSE_TEXT_2="Hello world from updated by swoole:server:reload commmand!";
CONTROLLER_TEMPLATE_FILE='../TestBundle/Controller/ReplacedContentTestController.php.tmpl'
CONTROLLER_FILE='../TestBundle/Controller/ReplacedContentTestController.php'
EXIT_CODE=0

TEMPLATE=$(< ${CONTROLLER_TEMPLATE_FILE})

# Place initial controller
CONTENTS=${TEMPLATE//"%REPLACE%"/$RESPONSE_TEXT_1}
echo "$CONTENTS" > ${CONTROLLER_FILE};

./console swoole:server:start --port ${PORT} --ansi

echo "[Info] Executing curl request: $CURL_REQUEST";
RESULT=$(curl ${CURL_REQUEST} -s)
echo "[Info] Result: $RESULT";
if [[ "$RESULT" != "$RESPONSE_TEXT_1" ]]; then
    echo "[Test] FAIL";
    exit 2;
fi


# Replace controller content
sleep 1;
CONTENTS=${TEMPLATE//"%REPLACE%"/$RESPONSE_TEXT_2}
echo "$CONTENTS" > ${CONTROLLER_FILE};
./console swoole:server:reload --ansi
sleep 1;

echo "[Info] Executing curl request: $CURL_REQUEST";
RESULT=$(curl ${CURL_REQUEST} -s)
echo "[Info] Result: $RESULT";
if [[ "$RESULT" = "$RESPONSE_TEXT_2" ]]; then
    echo "[Test] OK";
else
    echo "[Test] FAIL";
    EXIT_CODE=1;
fi

./console swoole:server:stop --ansi

CONTENTS=${TEMPLATE//"%REPLACE%"/$ORIGINAL_TEXT}
echo "$CONTENTS" > ${CONTROLLER_FILE};

exit ${EXIT_CODE};
