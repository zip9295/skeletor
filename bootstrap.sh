#!/usr/bin/env bash

# crate swap
sudo /bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
sudo /sbin/mkswap /var/swap.1
sudo /sbin/swapon /var/swap.1

sudo apt install software-properties-common
sudo apt-add-repository -y ppa:ondrej/php
sudo apt-get update
sudo apt-get upgrade -y

echo "
   Installing MySql - dbName: skeleton | user:root | password:rootpass
***************************************************************"
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password password rootpass"
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password_again password rootpass"

sudo apt-get install -y mysql-common mysql-server mysql-client
mysql -u root -prootpass  -e "CREATE DATABASE skeleton;"

echo "
   Installing PHP 8.x...
***************************************************************"
sudo apt-get install -y zip unzip imagemagick
sudo apt-get install -y nginx
sudo apt-get install -y curl git redis-server
sudo apt-get install -y software-properties-common xpdf
sudo apt-get install -y php8.4-common php8.4-cli php8.4-fpm
sudo apt-get install -y php8.4-{bz2,curl,mysql,readline,xml,gd,dev,mbstring,opcache,zip,xsl,dom,intl,redis,igbinary}
sudo apt-get install -y php8.4-{xdebug,imagick,mcrypt}

cd /vagrant/

if [ -d "vendor" ]; then
 php composer.phar update
else
 php composer.phar install
fi

cd /vagrant/data/phinx && /vagrant/vendor/bin/phinx migrate

sudo bash -c "echo 'server {
    listen 80;
    sendfile off;
    root /vagrant/public;
    index index.php index.html index.htm;
    server_name skeleton.local;
    rewrite ^/@[^/]+/(.*)$ /\$1 last;
    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }
    client_max_body_size 16M;
    client_body_buffer_size 2M;

    error_page 404 /404.html;
    error_page 500 502 503 504 /50x.html;
    location = /50x.html {
        root /usr/share/nginx/html;
    }

    location ~ \.php$ {
        try_files \$uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param APPLICATION_ENV development;
    }
}' > /etc/nginx/sites-available/skeleton.local"

sudo ln -s /etc/nginx/sites-available/skeleton.local /etc/nginx/sites-enabled/skeleton.local
sudo service nginx restart

echo "
   Add this line to hosts file :
   192.168.5.11 skeleton.local
"