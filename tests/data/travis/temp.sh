#!/bin/sh -e
find . -depth | \
while read LONG; do
   SHORT=$( basename "$LONG" | tr '[:lower:]' '[:upper:]' )
   DIR=$( dirname "$LONG" )
   if [ "${LONG}" != "${DIR}/${SHORT}"  ]; then
     mv "${LONG}" "${DIR}/${SHORT}"
   fi
done
