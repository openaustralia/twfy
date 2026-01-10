all:
	docker build -t twfy-app .
	docker compose up -d
lint:
	./scripts/lint.sh www