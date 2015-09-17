#!/bin/sh
php ../php2c.php --extname=sample --phpfile=sample.php
cd project
phpize
./configure
make