# This script will be run on clevercloud instance after each deploy.

# Play migrations without asking user input.
php bin/console doctrine:migrations:migrate --no-interaction
