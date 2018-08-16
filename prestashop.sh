#!/bin/sh -e

#=============================================================================
#  Use this script build hipay images and run Hipay Professional's containers
#
#==============================================================================
if [ "$1" = '' ] || [ "$1" = '--help' ];then
    printf "\n                                                                                  "
    printf "\n ================================================================================ "
    printf "\n                                  HiPay'S HELPER                                 "
    printf "\n                                                                                  "
    printf "\n For each commands, you may specify the prestashop version "16" or "17"           "
    printf "\n ================================================================================ "
    printf "\n                                                                                  "
    printf "\n                                                                                  "
    printf "\n      - init      : Build images and run containers (Delete existing volumes)     "
    printf "\n      - restart   : Run all containers if they already exist                      "
    printf "\n      - up        : Up containters                                                "
    printf "\n      - exec      : Bash prestashop.                                              "
    printf "\n      - log       : Log prestashop.                                               "
    printf "\n                                                                                  "
fi

if [ "$1" = 'init' ] && [ "$2" = '' ];then
     docker-compose -f docker-compose.dev.yml stop prestashop16 prestashop17 database smtp
     docker-compose -f docker-compose.dev.yml rm -fv prestashop16 prestashop17 database smtp
     rm -Rf data/
     rm -Rf web16/
     rm -Rf web17/
     docker-compose -f docker-compose.dev.yml build --no-cache database smtp prestashop16 prestashop17
     docker-compose -f docker-compose.dev.yml up -d  database smtp prestashop16 prestashop17
fi

if [ "$1" = 'init' ] && [ "$2" != '' ];then
     docker-compose -f docker-compose.dev.yml stop database smtp prestashop"$2"
     docker-compose -f docker-compose.dev.yml rm database smtp -fv prestashop"$2"
     rm -Rf data/
     rm -Rf web16/
     rm -Rf web17/
     docker-compose -f docker-compose.dev.yml build --no-cache database smtp prestashop"$2"
     docker-compose -f docker-compose.dev.yml up  -d database smtp prestashop"$2"
fi

if [ "$1" = 'restart' ];then
     docker-compose -f docker-compose.dev.yml  stop database smtp prestashop16 prestashop17
     docker-compose -f docker-compose.dev.yml  up -d database smtp prestashop16 prestashop17
fi

if [ "$1" = 'kill' ];then
     docker-compose -f docker-compose.dev.yml stop database smtp prestashop16 prestashop17
     docker-compose -f docker-compose.dev.yml rm -fv database smtp prestashop16 prestashop17
     rm -Rf data/
     rm -Rf web16/
     rm -Rf web17/
fi

if [ "$1" = 'exec' ] && [ "$2" != '' ];then
     docker exec -it hipay-professional-shop-ps"$2" bash
fi

if [ "$1" = 'log' ] && [ "$2" != '' ];then
    docker logs -f hipay-professional-shop-ps"$2"
fi


