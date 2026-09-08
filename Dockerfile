# Use the official PHP 8.2 image with Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite for friendly URLs (if needed in the future)
RUN a2enmod rewrite

# Update package lists and install SQLite3 libraries
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install the PDO SQLite PHP extension
RUN docker-php-ext-install pdo_sqlite

# Increase file upload limits for JSON imports (defaults are usually 2MB)
RUN echo "upload_max_filesize = 50M\npost_max_size = 50M\n" > /usr/local/etc/php/conf.d/uploads.ini

# Set the working directory to the Apache web root
WORKDIR /var/www/html

# Copy the entire application source code into the container
COPY . /var/www/html/

# Create the database directory explicitly (just in case it's missing from the source)
RUN mkdir -p /var/www/html/database

# Critical Step: Set ownership of the database directory to the Apache user (www-data)
# SQLite requires write permissions to BOTH the database file AND the directory it resides in
RUN chown -R www-data:www-data /var/www/html/database \
    && chmod -R 775 /var/www/html/database

# Expose port 80 (Apache's default port)
EXPOSE 80
