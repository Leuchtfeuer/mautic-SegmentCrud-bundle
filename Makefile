# Dev tooling
# Available inside container after `sudo apt-get update && sudo apt-get install -y make`

COMPOSER ?= composer
PHP_CS_FIXER ?= vendor/bin/php-cs-fixer
PHPUNIT ?= vendor/bin/phpunit
PHPSTAN ?= vendor/bin/phpstan
PHPSTAN_CONFIG ?= phpstan.neon.dist

.PHONY: help install cs-fix cs-check phpstan test test-coverage check-dod all

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

install: ## composer install
	$(COMPOSER) install

cs-fix: install ## Run PHP CS Fixer (fix files)
	$(PHP_CS_FIXER) fix --verbose

cs-check: install ## PHP CS Fixer dry-run (CI)
	$(PHP_CS_FIXER) fix --dry-run --diff --verbose

phpstan: install ## PHPStan static analysis ($(PHPSTAN_CONFIG))
	$(PHPSTAN) analyse -c $(PHPSTAN_CONFIG) --no-progress --memory-limit=512M

test: install ## Run PHPUnit (unit tests only; functional need Mautic app kernel / DB)
	$(PHPUNIT) -c phpunit.xml --testsuite unit

test-coverage: install ## Run PHPUnit (unit) with text coverage
	$(PHPUNIT) -c phpunit.xml --testsuite unit --coverage-text

check-dod: ## Leuchtfeuer DoD (composer.json, config.php, README.md)
	bash checkDod.sh

all: cs-fix phpstan test ## Run cs-fix + phpstan + test
