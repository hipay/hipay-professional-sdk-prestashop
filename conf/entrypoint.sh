#!/bin/sh

#!/bin/sh -e

/tmp/docker_run.sh

#install module HiPay Professionnal

echo "\n* Starting install module HiPay Professionnal ...";

php /var/www/html/hipay_install.php

echo "\n* End HiPay treatment ...";


echo "\n* Almost ! Starting Apache now\n";
exec apache2 -DFOREGROUND

