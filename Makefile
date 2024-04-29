build:
	docker-compose build

run:
	docker-compose up -d

install:
	docker-compose exec php bash -c 'composer setup'

migrate:
	docker-compose exec php bash -c 'php artisan migrate'

config-clear:
	docker-compose exec php bash -c 'php artisan config:clear'

compile: build run install migrate config-clear

down:
	docker-compose down

stop:
	docker-compose stop
