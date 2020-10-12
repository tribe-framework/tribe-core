# wildfire

## minimum requirements
apache, php7, mysql8 (LAMP server) on any operating system

## quick install
```
read -p "Website Domain: " websitedomain; read -p "IP Address: " ipv4address; read -p "MySQL Root Username: " mysqluser; read -p "MySQL Root Password: " mysqlpass; read -p "MySQL Website Username: " mysqlwebsiteuser; read -p "MySQL website Password: " mysqlwebsitepass; sudo git clone https://github.com/wil-ldf-ire/core.git /var/www/html/$websitedomain; sudo sed -i "s/xyz.com/$websitedomain/g" /var/www/html/$websitedomain/config/website.sh; sudo sed -i "s/ipv4_address/$ipv4address/g" /var/www/html/$websitedomain/config/website.sh; sudo sed -i "s/mysql_root_user/$mysqluser/g" /var/www/html/$websitedomain/config/website.sh; sudo sed -i "s/mysql_root_pass/$mysqlpass/g" /var/www/html/$websitedomain/config/website.sh; sudo sed -i "s/xyz_com/$mysqlwebsiteuser/g" /var/www/html/$websitedomain/config/website.sh; sudo sed -i "s/xyz_pass/$mysqlwebsitepass/g" /var/www/html/$websitedomain/config/website.sh; sudo bash /var/www/html/$websitedomain/config/website.sh; sudo rm /var/www/html/$websitedomain/config/website.sh;
```

## install instructions
1. use config/install.sql for mysql database structure
2. include database details, folder path, website url in /config/vars.php
3. give suitable permissions to uploads folder, eg. on ubuntu: chown www-data:www-data uploads -R
4. use /themes/wildfire-2020/config/menus.json to update menu items
5. use /themes/wildfire-2020/config/types.json to update content type and user type details

## upgrade instructions
git pull https://github.com/wildfire-dev/core.git

### note
if you're not using git to update - config, uploads and themes folders need not be updated with new versions of wildfire template, unless otherwise mentioned

## backup instructions
1. sudo apt-get install s3cmd
2. create a s3 folder, dis-allow versioning, allow logging in same directory, go to folder settings - complaince settings - set complaince mode ON, keep detele retention 3 months
3. create a user and change user home path to folder name
4. save s3 user access and secret key details /path/to/theme/config/vars.php
5. crontab -e : @daily  php /path/to/config/backup.php > /dev/null 2>&1

## contact
write to tech@connect.wf if you face any issues or have suggestions 