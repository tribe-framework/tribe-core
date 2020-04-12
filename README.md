# wildfire

## minimum requirements
apache, php7, mysql8 on any operating system

## install instructions
1. use config/install.sql for mysql database structure
2. include database details, folder path, website url in config/config-vars.php
3. give suitable permissions to uploads folder, eg. on ubuntu: chown www-data:www-data uploads -R
4. use /themes/wildfire-2020/config/menus.json to update menu items
5. use /themes/wildfire-2020/config/types.json to update content type and user type details

## upgrade instructions
git pull https://github.com/wildfirego/demo.wildfirego.com.git

### note
if you're not using git to update - config, uploads and themes folders need not be updated with new versions of wildfire template, unless otherwise mentioned

## contact
write to tech@wildfire.world if you face any issues or have suggestions 