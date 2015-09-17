#!/bin/sh
cd test
source ./0-clear-project.sh
source ./1-generate-cfile.sh
source ./2-prepare-env.sh
source ./3-build-module.sh
source ./4-test-module.sh