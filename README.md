marketanalyzer
==============

recreational market adventure tool

the code is dirty and sloppy - this will get fixed. I hobbled it into classes to seperate stuff out slightly. 

IF you want to play along, check the code out into a folder that you can access with your web browser.

After that install mysql and import the .sql file. This includes creating and naming the new db 'abbastoons_stock'. If you dislike the name you can easily rename it and simply adjust the name in the core classes file via the db connect function

This should get you up and rolling.

The analyze has a few basic functions that display the current days activity and a range of activity from the previous 6 days.

You could get more days and include moving average, implment a visual chart for the data, add other metrics,etc etc etc 

The goal is to get a nice tool for a bot to use that we can simulate trading with. The bot will be built with 'pluggable strategys'
so we can define multiple strategies or trading algorithms and switch them in and out or pit them against each other to do
performance evaluations. 

There is a nice repo on google code for yahoos csv api

https://code.google.com/p/yahoo-finance-managed/wiki/CSVAPI

How simple is stuffing a yahoo url with the variables you want?


