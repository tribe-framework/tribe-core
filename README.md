# wildfire

## minimum requirements
apache, php7, mysql8 (LAMP server) on any operating system

## quick install
```
wget https://raw.githubusercontent.com/wil-ldf-ire/core/develop/1.1.0/config/install.sh; sudo bash install.sh;
```

## install instructions
1. use config/install.sql for mysql database structure
2. include database details, folder path, website url in /config/vars.php
3. give suitable permissions to uploads folder, eg. on ubuntu: chown www-data:www-data uploads -R
4. use /themes/wildfire-2020/config/menus.json to update menu items
5. use /themes/wildfire-2020/config/types.json to update content type and user type details

## upgrade instructions
git pull https://github.com/wildfire-dev/core.git

## uninstall instructions
```
sudo bash /path/to/config/uninstall.sh
```

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