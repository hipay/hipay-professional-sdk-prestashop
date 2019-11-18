#!/bin/sh -e

#===================================#
#       CALL PARENT ENTRYPOINT
#===================================#
echo "\n Execution PRESTASHOP Entrypoint \n";

# wait until MySQL is really available
maxcounter=45
counter=1
while ! mysql --protocol TCP -h $DB_SERVER -P $DB_PORT -u $DB_USER -p$DB_PASSWD -e "show databases;" > /dev/null 2>&1; do
    sleep 1
    counter=`expr $counter + 1`
    if [ $counter -gt $maxcounter ]; then
        >&2 echo "We have been waiting for MySQL too long already; failing."
        exit 1
    fi;
done

/tmp/docker_run.sh

if [ $ACTIVE_XDEBUG ];then
    # INSTALL X DEBUG
    echo '' | pecl install xdebug-2.6.1
    echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini
    echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini
fi

#===================================#
#       START WEBSERVER
#===================================#
echo "\n* Starting Apache now\n";
exec apache2-foreground

