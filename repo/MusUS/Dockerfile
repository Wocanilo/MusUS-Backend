FROM php:7.4.5-apache-buster

# Install Mysql PDO support
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite
# Inform Docker that the container is listening on the specified port at runtime.
EXPOSE 80
# Replace default config
COPY ./000-default.conf /etc/apache2/sites-available/
# Install MusUS
RUN apt-get update && apt-get install -y git

# Backend install
WORKDIR /var/www/html
RUN git clone https://github.eii.us.es/dansesben/MusUS-Backend

# Install node
RUN curl -sL https://deb.nodesource.com/setup_14.x | bash -
RUN apt-get install -y nodejs


RUN git clone https://github.eii.us.es/dansesben/MusUS-Frontend
WORKDIR /var/www/html/MusUS-Frontend
# Patch api route
RUN sed -i 's/https/http/g' src/store/index.js

RUN npm install
RUN npm run build

# Run apache on container start
CMD ["apache2-foreground"]