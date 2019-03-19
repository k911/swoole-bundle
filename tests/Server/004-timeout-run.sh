#!/usr/bin/env bash

cd "$( dirname "${BASH_SOURCE[0]}" )/../Fixtures/Symfony/app";

timeout 3 ./console swoole:server:run --ansi

if [[ "$?" == "124" ]]; then
    exit 0;
fi
