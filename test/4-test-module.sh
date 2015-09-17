#!/bin/sh
cd project
php -n -d extension_dir=./modules -d extension=sample.so ../test.php
cd ..