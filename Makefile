install:
	composer install

validate:
	composer validate

PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src public
	composer exec --verbose phpstan -- analyse --memory-limit=512M src public

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 src public

test:
	composer exec --verbose phpunit tests

test-coverage:
	mkdir -p build/logs
	XDEBUG_MODE=coverage vendor/bin/phpunit tests --coverage-clover=build/logs/clover.xml --coverage-filter=src

test-coverage-text:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-textпш