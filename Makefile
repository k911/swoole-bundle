.PHONY: clean/coverage
clean/coverage:
	@rm -rf cov/*
	@rm -rf clover.xml

.PHONY: clean/tests/resources
clean/tests/resources:
	@rm -rf tests/Fixtures/resources/*.pid

.PHONY: clean/fixtures/cache
clean/fixtures/cache:
	@rm -rf tests/Fixtures/Symfony/app/var/cache/*

.PHONY: clean/fixtures/logs
clean/fixtures/logs:
	@rm -rf tests/Fixtures/Symfony/app/var/log/*

.PHONY: clean
clean: clean/coverage clean/fixtures/cache clean/fixtures/logs clean/tests/resources
