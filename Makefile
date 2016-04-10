sniff:
	vendor/bin/phpcs ./src --standard='./coding_standard.xml' -n

phpunit:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-html ./.coverage

MKDOCS := $(shell mkdocs -V)

mkdocs:
ifndef MKDOCS
	pip install mkdocs
endif
	mkdocs build --clean
