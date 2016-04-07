sniff:
	vendor/bin/phpcs ./src --standard=PSR2 && \
	vendor/bin/phpcs ./src --standard=Squiz --sniffs=Squiz.Commenting.FunctionComment,Squiz.Commenting.FunctionCommentThrowTag,Squiz.Commenting.ClassComment,Squiz.Commenting.VariableComment

phpunit:
	vendor/bin/phpunit

coverage:
	vendor/bin/phpunit --coverage-html ./.coverage
