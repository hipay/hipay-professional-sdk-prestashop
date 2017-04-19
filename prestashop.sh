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
    docker-compose stop
    docker-compose rm -fv
    rm -Rf data/
    rm -Rf web16/
    rm -Rf web17/
    docker-compose -f docker-compose.yml -f docker-compose-16.yml -f docker-compose-17.yml build --no-cache
    docker-compose -f docker-compose.yml -f docker-compose-16.yml -f docker-compose-17.yml up -d
fi

if [ "$1" = 'init' ] && [ "$2" != '' ];then
    docker-compose stop
    docker-compose rm -fv
    rm -Rf data/
    rm -Rf web16/
    rm -Rf web17/
    docker-compose -f docker-compose.yml -f  docker-compose-"$2".yml build --no-cache
    docker-compose -f docker-compose.yml -f docker-compose-"$2".yml up  -d
fi

if [ "$1" = 'restart' ];then
    docker-compose stop
    docker-compose -f docker-compose.yml -f docker-compose-16.yml -f docker-compose-17.yml up -d
fi

if [ "$1" = 'up' ] && [ "$2" != '' ];then
    docker-compose -f docker-compose.yml -f docker-compose-"$2".yml up  -d
fi

if [ "$1" = 'exec' ] && [ "$2" != '' ];then
    docker exec -it jira-feature-ps"$2"-pro.hipay-pos-platform.com bash
fi

if [ "$1" = 'log' ] && [ "$2" != '' ];then
    docker logs -f jira-feature-ps"$2"-pro.hipay-pos-platform.com
fi


