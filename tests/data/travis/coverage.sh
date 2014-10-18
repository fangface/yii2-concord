#!/bin/sh -e
if [ "$(expr "$TRAVIS_PHP_VERSION" "=" "5.5")" -eq 1 ]; then
	wget https://scrutinizer-ci.com/ocular.phar
	php ocular.phar code-coverage:upload --format=php-clover coverage.clover
fi
