#!/bin/sh
set -ex
hhvm --version

if [ "$TRAVIS_PHP_VERSION" = 'hhvm-3.24' ]; then
  cp composer.lock-3.24 composer.lock
fi

composer install

hh_client
hhvm vendor/bin/phpunit
hhvm vendor/bin/hhast-lint

# Make sure we pass when a release is required
SOURCE_DIR=$(pwd)
EXPORT_DIR=$(mktemp -d)
git archive --format=tar -o "${EXPORT_DIR}/exported.tar" HEAD
cd "$EXPORT_DIR"
tar -xf exported.tar
cp "${SOURCE_DIR}/composer.lock" .
composer install --no-dev
echo > .hhconfig
hh_server --check $(pwd)
