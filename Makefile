all:
	@echo "Available targets:"
	@echo "  docker-build   Build the Docker image for the application"
	@echo "  docker-run     Run the Docker container for the application"
	@echo "  lint           Run linting on the www directory"

docker-build:
	docker build -t twfy-app .
docker-run:
	docker compose up -d

docker: docker-build docker-run

lint:
	./scripts/lint.sh www
