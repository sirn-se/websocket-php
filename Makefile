install: composer.phar
	./composer.phar install

update: composer.phar
	./composer.phar self-update
	./composer.phar update

test: composer.lock
	./vendor/bin/phpunit

cs: composer.lock
	./vendor/bin/phpcs

stan: composer.lock
	./vendor/bin/phpstan analyse --memory-limit 256M

coverage: composer.lock
	./vendor/bin/phpunit --coverage-clover coverage/clover.xml --coverage-html=coverage -d --min-coverage=100

composer.phar:
	curl -s http://getcomposer.org/installer | php

composer.lock: composer.phar
	./composer.phar --no-interaction install

vendor/bin/phpunit: install

clean:
	rm composer.lock
	rm -r vendor
	rm -r coverage
