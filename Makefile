test: pretty static
	./vendor/bin/phpunit

pretty:
	PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --diff --dry-run --allow-risky=yes

pretty-fix:
	PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix --allow-risky=yes

static:
	vendor/bin/psalm --no-cache --threads $(nproc)
	vendor/bin/psalm --taint-analysis --threads $(nproc)
