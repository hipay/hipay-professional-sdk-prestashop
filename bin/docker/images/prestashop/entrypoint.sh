#!/bin/sh -e

#===================================#
#       CALL PARENT ENTRYPOINT
#===================================#
echo "\n Execution PRESTASHOP Entrypoint \n";
/tmp/docker_run.sh

if [ $ACTIVE_XDEBUG ];then
    # INSTALL X DEBUG
    echo '' | pecl install xdebug-2.5.0
    echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
fi

#===================================#
#       START WEBSERVER
#===================================#
echo "\n* Starting Apache now\n";
exec apache2-foreground

