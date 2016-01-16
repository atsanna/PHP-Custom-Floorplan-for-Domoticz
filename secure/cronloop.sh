#!/bin/bash
for loop in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20 21 22 23 24 25 26 27 28 29 30 31 32 33 34 35 36 37 38 39 40 41 42 43 44 45 46 47 48 49 50 51 52 53 54 55 56 57 58 59; do
	SECOND=$(date +"%S")
	if [ $SECOND -eq "59" ] ; then
		/usr/bin/php /var/www/secure/cron.php all > /dev/null 2>&1 &
		break
	else
		/usr/bin/php /var/www/secure/cron.php loop > /dev/null 2>&1 &
		sleep 1
	fi
done
