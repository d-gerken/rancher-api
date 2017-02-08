FROM php:7.1-cli

# Add cli app
COPY cli/ /var/www/html/cli/

# Set workdir
WORKDIR /var/www/html/cli

# Enable console
CMD [ "php", "./console.php" ]
