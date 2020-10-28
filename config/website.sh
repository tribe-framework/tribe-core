installpath1=$(echo "install_path" | sed 's/\//\\\//g');
sudo chown ubuntu:ubuntu install_path/xyz.com -R;
sudo chown www-data:www-data install_path/xyz.com/uploads -R;

sudo cp install_path/xyz.com/config/nginx.conf /etc/nginx/sites-available/xyz.com;
sudo cp install_path/xyz.com/config/nginx.app.conf /etc/nginx/sites-available/app.xyz.com;

sudo sed -i 's/your_server_ip/ipv4_address/g' /etc/nginx/sites-available/xyz.com;
sudo sed -i 's/your_server_ip/ipv4_address/g' /etc/nginx/sites-available/app.xyz.com;

sudo sed -i 's/your_server_base_dir/xyz.com/g' /etc/nginx/sites-available/xyz.com;
sudo sed -i 's/your_server_domain/xyz.com/g' /etc/nginx/sites-available/xyz.com;
sudo sed -i 's/your_server_base_dir/xyz.com/g' /etc/nginx/sites-available/app.xyz.com;
sudo sed -i 's/your_server_domain/app.xyz.com/g' /etc/nginx/sites-available/app.xyz.com;

sudo sed -i 's/xyz-port-var/xyz_port/g' /etc/nginx/sites-available/xyz.com;
sudo sed -i 's/xyz-port-var/xyz_port/g' /etc/nginx/sites-available/app.xyz.com;

sudo ln -s /etc/nginx/sites-available/xyz.com /etc/nginx/sites-enabled/xyz.com;
sudo ln -s /etc/nginx/sites-available/app.xyz.com /etc/nginx/sites-enabled/app.xyz.com;

sudo cp install_path/xyz.com/config/apache2.conf /etc/apache2/sites-available/xyz.com.conf;
sudo cp install_path/xyz.com/config/apache2.conf /etc/apache2/sites-available/app.xyz.com.conf;

sudo sed -i 's/your_server_base_dir/xyz.com/g' /etc/apache2/sites-available/xyz.com.conf;
sudo sed -i 's/your_server_domain/xyz.com/g' /etc/apache2/sites-available/xyz.com.conf;
sudo sed -i 's/your_server_base_dir/xyz.com/g' /etc/apache2/sites-available/app.xyz.com.conf;
sudo sed -i 's/your_server_domain/app.xyz.com/g' /etc/apache2/sites-available/app.xyz.com.conf;

sudo sed -i 's/your_server_email/admin_email/g' /etc/apache2/sites-available/xyz.com.conf;
sudo sed -i 's/your_server_email/admin_email/g' /etc/apache2/sites-available/app.xyz.com.conf;

sudo sed -i "s/your_server_path/$installpath1/g" /etc/apache2/sites-available/xyz.com.conf;
sudo sed -i "s/your_server_path/$installpath1/g" /etc/apache2/sites-available/app.xyz.com.conf;

a2ensite xyz.com;
a2ensite app.xyz.com;

sudo systemctl reload nginx;
sudo service apache2 start;

sudo certbot --agree-tos --no-eff-email --email admin_email --nginx -d xyz.com -d www.xyz.com;
sudo certbot --agree-tos --no-eff-email --email admin_email --nginx -d app.xyz.com -d www.app.xyz.com;

sudo service apache2 restart;

sudo git clone https://github.com/wil-ldf-ire/core-theme.git install_path/xyz.com/themes/xyz.com;
sudo touch -c install_path/xyz.com/themes/xyz.com/config/vars.php;
echo -e "<?php //ENV as DEV for getting all error messages; \ndefine('ENV', 'LIVE'); \ndate_default_timezone_set('Asia/Kolkata'); \ndefine('UPLOAD_FILE_TYPES', '/\.(zip|png|jpe?g|gif|pdf|doc|docx|xls|xlsx|mov|mp4|vtt)$/i'); \ndefine('CONTACT_EMAIL', ''); \ndefine('WEBSITE_NAME', ''); \ndefine('CONTACT_NAME', ''); \ndefine('S3_BKUP_HOST_BASE', 's3.wasabisys.com'); \ndefine('S3_BKUP_HOST_BUCKET', '%(bucket)s.s3.wasabisys.com'); \ndefine('S3_BKUP_ACCESS_KEY', ''); \ndefine('S3_BKUP_SECRET_KEY', ''); \ndefine('S3_BKUP_FOLDER_NAME', BARE_URL); \n?>" >> install_path/xyz.com/themes/xyz.com/config/vars.php;
sudo chown ubuntu:ubuntu install_path/xyz.com/themes/xyz.com -R;
sudo cp install_path/xyz.com/config/vars.php.sample install_path/xyz.com/config/vars.php;
sudo sed -i 's/xyz-domain-var/xyz.com/g' install_path/xyz.com/config/vars.php;
sudo sed -i 's/xyz-db-name-var/mysql_w_user/g' install_path/xyz.com/config/vars.php;
sudo sed -i 's/xyz-db-pass-var/mysql_w_pass/g' install_path/xyz.com/config/vars.php;
sudo sed -i "s/xyz-install-path/$installpath1/g" install_path/xyz.com/config/vars.php;
sudo sed -i 's/xyz-db-pass-var/mysql_w_pass/g' install_path/xyz.com/config/install.php;
sudo sed -i 's/your_server_email/admin_email/g' install_path/xyz.com/config/install.php;
sudo sed -i "s/xyz-install-path/$installpath1/g" install_path/xyz.com/config/install.php;
sudo sed -i 's/xyz-domain-var/xyz.com/g' install_path/xyz.com/config/install.php;
echo "CREATE USER 'mysql_w_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'mysql_w_pass'; FLUSH PRIVILEGES;" | mysql -umysql_root_user -pmysql_root_pass -hlocalhost;
echo "CREATE DATABASE mysql_w_user CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" | mysql -umysql_root_user -pmysql_root_pass -hlocalhost;
echo "GRANT ALL PRIVILEGES on mysql_w_user.* to 'mysql_w_user'@'localhost';" | mysql -umysql_root_user -pmysql_root_pass -hlocalhost;
sudo mysql -umysql_w_user -pmysql_w_pass mysql_w_user < install_path/xyz.com/config/install.sql;
sudo bash config/composer.sh;
php composer.phar install;
php composer.phar dump-autoload;
sudo php install_path/xyz.com/config/install.php;
sudo rm install_path/xyz.com/config/install.*;
sudo rm install_path/xyz.com/config/website.sh;
sudo rm install_path/xyz.com/config/composer.sh;
sudo rm install_path/xyz.com/config/*.conf;
sudo rm install_path/xyz.com/config/*.sample;

sudo /bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024;
sudo /sbin/mkswap /var/swap.1;
sudo /sbin/swapon /var/swap.1;

sudo service nginx restart;

#cd install_path/xyz.com/themes/xyz.com;
#sudo quasar create app;
#cd app;
#sudo quasar build;
#pm2 --name app.xyz.com start "sudo quasar serve -p xyz_port -H localhost"
#cd install_path/xyz.com;

#sudo sed -i 's/xyz-domain-var/app.xyz.com/g' install_path/xyz.com/themes/xyz.com/app/README.md;
#sudo sed -i 's/xyz-domain-var/app.xyz.com/g' install_path/xyz.com/themes/xyz.com/app/quasar.conf.js;
#sudo sed -i 's/xyz-port-var/xyz_port/g' install_path/xyz.com/themes/xyz.com/app/quasar.conf.js;
#sudo sed -i 's/xyz-domain-var/app.xyz.com/g' install_path/xyz.com/themes/xyz.com/app/package.json;
#add pm2 to linux reboot - https://www.digitalocean.com/community/tutorials/how-to-set-up-a-node-js-application-for-production-on-ubuntu-20-04