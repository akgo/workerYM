#!/bin/sh
# @Author: Michael Gan
# @URL:http://blog.missyi.com
# @  Svn Commit Auto Shell
statuslist=`svn status`
count=0;
 
for x in ${statuslist}
 do
 
        start=$(($count%2))
 
        if [ "$start" = "0" ]
         then
                type=$x
        fi
 
        if [ "$start" = "1" ]
         then
                file=$x
                case "$type" in
                    "!")
                          svn delete $file
                        ;;
                    "?")
                          svn add $file
                        ;;
                esac
        fi
 
        count=$(($count+1)) 
 
 done
 
 if [ "$count" -gt 0 ]
     then
         svn ci -m "$1"
 fi
