# Dev tooling (run inside DDEV / with Mautic root as ../..)
# Available inside container after `sudo apt-get update && sudo apt-get install -y make`

COMPOSER ?= composer

.PHONY: help install cs-fix cs-check phpstan test test-coverage check-dod all

help: ## Show this help
	@grep -hE '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-18s\033[0m %s\n", $$1, $$2}'

install: ## No-op when developing inside a Mautic checkout (use root vendor)
	@echo "Use Mautic root tooling via composer scripts (make all)."

cs-fix: ## Run PHP CS Fixer (fix files) via Mautic root binary
	$(COMPOSER) run fixcs

cs-check: ## PHP CS Fixer dry-run
	../../bin/php-cs-fixer fix --config .php-cs-fixer.php --dry-run --diff --verbose

phpstan: ## PHPStan via Mautic root binary
	$(COMPOSER) run phpstan

test: ## PHPUnit (unit + functional) via composer scripts
	$(COMPOSER) run test

test-coverage: ## PHPUnit with text coverage
	$(COMPOSER) run coverage

check-dod: ## Leuchtfeuer DoD (composer.json, config.php, README.md)
	$(COMPOSER) run dod

all: ## Rector + CS + PHPStan + PHPUnit + DoD
	$(COMPOSER) run all
