# MusUS

## Installation
Modify the ```/etc/hosts``` file (on Linux) adding the following entries
```
127.0.0.1      api.musus.wocat.xyz
127.0.0.1      musus.wocat.xyz
```
Then go into the repo folder and execute ``` docker-compose up ``` as root. When the database and web server are up, go to http://api.musus.wocat.xyz/install.php to initialize the database. The message ```Installed``` will appear if successful.

Now the website is available at http://musus.wocat.xyz