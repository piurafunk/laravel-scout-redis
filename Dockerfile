FROM    php:7.2-apache as baseDependencies

ARG     APACHE_DOCUMENT_ROOT=/var/www/html/public
ARG     USER_ID=1000
ARG     GROUP_ID=1000

RUN     sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf
RUN     sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Install base binaries
RUN     apt update && apt install -y \
			libcap2-bin && \
		apt clean && \
		rm -rf /var/lib/apt/lists/*

# Enable mod rewrite
RUN		a2enmod rewrite

# Change www-data user and group IDs
RUN		groupmod -o -g "$GROUP_ID" www-data && \
		usermod -o -u "$USER_ID" www-data

# Allow www-data to run apache as non-privileged
RUN		setcap 'cap_net_bind_service=+ep' /usr/sbin/apache2 && \
		chown -R www-data: \
			/var/www \
			/var/lock/apache2 \
			/var/log/apache2 \
			/var/run/apache2

FROM    baseDependencies as dev

# Install dev binaries
RUN     apt update && apt install -y \
			git \
			unzip \
			vim \
			wget \
			zip && \
		apt clean && \
		rm -rf /var/lib/apt/lists/*

# Download and install composer as /usr/sbin/composer
RUN		EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)" && \
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
        ACTUAL_SIGNATURE="$(php -r "echo hash_file('SHA384', 'composer-setup.php');")" && \
        if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then \
	        >&2 echo 'ERROR: Invalid installer signature'  && \
	        rm composer-setup.php  && \
	        exit 1; \
	    fi; \
	    \
	    php composer-setup.php --quiet  && \
	    rm composer-setup.php && \
	    mv composer.phar /usr/bin/composer