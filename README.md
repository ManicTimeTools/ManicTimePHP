About
===
ManicTimeWeb is a web interface for [ManicTime](http://www.manictime.com/). Its
purpose is to share tracking statistics with others or simply have a better 
interface for yourself. It supports many computers and can be used for an 
enterprise to centralize employee tracking. You will need the [ManicTimeMonitor](https://github.com/ManicTimeTools/ManicTimeMonitor),
a client written in C# that runs periodically (or manually) to send your tracking
data to ManicTimeWeb.


Requirements
===
 - MySQL
 - Webserver with PHP
 - [ManicTimeMonitor](https://github.com/ManicTimeTools/ManicTimeMonitor)
 
It works with sqlite3 but given the nature of the project it is much, much better
to use mysql. It may work with pgsql with some changes to the SQL queries.


Installation
===
Create a mysql database then open config.php and set the variables to the proper values.


License
===
The code is licensed under the [MIT license](http://choosealicense.com/licenses/mit/). See [LICENSE](LICENSE).