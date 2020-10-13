.PHONY: clean code-style coverage help test test-unit test-integration static-analysis infection-testing install-dependencies update-dependencies
.DEFAULT_GOAL := help

PHPSPEC = ./vendor/bin/phpspec run --format dot -vvv -c phpspec.yml
PHPSTAN  = ./vendor/bin/phpstan
PHPCS = ./vendor/bin/phpcs --extensions=php
INFECTION = ./vendor/bin/infection
CONSOLE = ./bin/console

clean:
	rm -rf ./build ./vendor

fix-code-style:
	${PHPCBF}

code-style:
	mkdir -p build/logs/phpcs
	${PHPCS}

coverage:
	mkdir -p build/logs/phpspec/coverage
	php -dpcov.enabled=1 -dpcov.directory=./src ${PHPSPEC}
	./vendor/bin/coverage-check build/logs/phpspec/coverage/coverage.xml 100

test: test-unit test-integration

test-unit:
	${PHPSPEC} --no-coverage

infection-testing:
	make coverage
	cp -f build/logs/phpspec/coverage/xml/index.xml build/logs/phpspec/coverage/junit.xml
	${INFECTION} --test-framework=phpspec --only-covered --coverage=build/logs/phpspec/coverage --min-msi=95 --threads=`nproc`

static-analysis:
	${PHPSTAN} analyse src --no-progress

install-dependencies:
	composer install

update-dependencies:
	composer update

help:
	# Usage:
	#   make <target> [OPTION=value]
	#
	# Targets:
	#   clean                Cleans the coverage and the vendor directory
	#   code-style           Check code style using phpcs
	#   coverage             Generate code coverage (html, clover)
	#   help                 You're looking at it!
	#   test (default)       Run all the tests
	#   test-unit            Run the unit tests with phpspec
	#   static-analysis      Run static analysis using phpstan
	#   install-dependencies Run composer install
	#   update-dependencies  Run composer update
