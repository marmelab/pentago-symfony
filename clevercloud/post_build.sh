# This script will be run on clevercloud instance after each deploy.

# Create DB if not exists
php bin/console doctrine:database:create --if-not-exists

# Play migrations without asking user input.
php bin/console doctrine:migrations:migrate --no-interaction
