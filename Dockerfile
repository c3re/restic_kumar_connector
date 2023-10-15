FROM debian:buster
RUN apt-get update \
  && apt-get upgrade -y \
  && apt-get install -y apache2 php php-curl php-mbstring \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*
RUN rm /var/www/html/index.html
ADD htdocs /var/www/html
RUN mkdir /var/www/data
RUN chown www-data:www-data /var/www/ -R
EXPOSE 80
ADD start.sh /usr/local/bin/start.sh
CMD /usr/local/bin/start.sh
