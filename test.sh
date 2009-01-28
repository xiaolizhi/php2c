#!/bin/sh

php php2c.php

phpize
./configure
make

php \
  -n -d extension_dir=./modules -d extension=sample.so \
  test.php
