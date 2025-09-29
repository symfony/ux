
services:
###> symfony/mercure-bundle ###
  mercure:
    image: dunglas/mercure
    restart: unless-stopped
    environment:
      # Uncomment the following line to disable HTTPS,
      #SERVER_NAME: ':80'
      MERCURE_PUBLISHER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
      MERCURE_SUBSCRIBER_JWT_KEY: '!ChangeThisMercureHubJWTSecretKey!'
      # Set the URL of your Symfony project (without trailing slash!) as value of the cors_origins directive
      MERCURE_EXTRA_DIRECTIVES: |
        cors_origins http://127.0.0.1:8000
    # Comment the following line to disable the development mode
    command: /usr/bin/caddy run --config /etc/caddy/dev.Caddyfile
    healthcheck:
      test: ["CMD", "curl", "-f", "https://localhost/healthz"]
      timeout: 5s
      retries: 5
      start_period: 60s
    volumes:
      - mercure_data:/data
      - mercure_config:/config
###< symfony/mercure-bundle ###

volumes:
###> symfony/mercure-bundle ###
  mercure_data:
  mercure_config:
###< symfony/mercure-bundle ###
