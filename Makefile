WORKING_DIR=$(CURDIR)

php-cs-check:
	$(WORKING_DIR)/vendor/bin/php-cs-fixer fix --dry-run --format=junit --diff

php-cs-fix:
	$(WORKING_DIR)/vendor/bin/php-cs-fixer fix

php-cs-fix-diff:
	$(WORKING_DIR)/vendor/bin/php-cs-fixer fix --dry-run --diff

test-unit:
	./vendor/bin/codecept run unit
