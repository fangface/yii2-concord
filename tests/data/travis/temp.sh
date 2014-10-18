#!/bin/sh -e
find ./ -type d -depth -print -exec rename 's/(.*)\/([^\/]*)/$1\/\L$2/' {} \;
