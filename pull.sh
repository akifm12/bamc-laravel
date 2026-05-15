#!/bin/bash
cd /opt/bamc_laravel
git pull origin master
php artisan config:clear
php artisan view:clear
php artisan cache:clear
