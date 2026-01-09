all:
	docker build -t twfy-app .
	docker compose up -d