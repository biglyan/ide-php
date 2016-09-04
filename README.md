# ide-php
## Minimal browser based IDE deployed as a single PHP file!

Sometimes you have to work without your development environment.
May be you are traveling, may be you are on a different computer.
Setting up your development environment on a new machine is a big pain.

There are browser based IDE projects out there which either have paid cloud access, or very difficult local install procedures. But the real developer needs much less, and needs it delivered much easier!

ide-php is a __single PHP file__ that provides a file browser, a text editor with syntax highlighting and a terminal emulator.

## Installation
Installation should be as easy as it gets;
 0. Just drop in the `ide.php` file in your server, 
 0. Run `php ide.php password` to set a login password,
 0. Edit the newly created ide.config.php file to suit your working environment

and you are ready to go!

For your own safety please host ide.php behind a reverse proxy like nginx or apache. Allow access only from HTTPS. Do not forget to proxy the WebSocket Server
through HTTPS. Here is a nginx configuration example:
```
server {
    listen 443 ssl;
    server_name example.com
    // SSL config here
    
   	location /ide.php {
   		root /var/www/ide-php;
   		try_files /ide.php =404;
   		include /etc/nginx/php_params;
   	}
   	location /ide.websocket {
   		proxy_buffering off;
   		proxy_pass http://127.0.0.1:9009;
   		proxy_http_version 1.1;
   		proxy_set_header Upgrade websocket;
   	    proxy_set_header Connection upgrade;
   	}
}
```
and here is the corresponding ide.config.php file:
```
<?php
if (!defined("INCLUDE_CONFIG")) { die; }
define("WORKING_DIRECTORY", "/var/www");
define("WEB_URL", "https://example.com/ide.php");
define("TERMINAL_IP", "127.0.0.1");
define("TERMINAL_PORT", "9009");
define("TERMINAL_COMMAND", "/bin/sh -i");
define("TERMINAL_WEBSOCKET_URL", "wss://example.com:443/ide.websocket");
define("TERMINAL_FIX_CRLF", true);
define("ENCRYPTION_KEY", "6UQkyq8wtb09gDRLoVrig7dFneJE00b3"); // CHANGE THIS!
define("ENCRYPTION_SALT", "VrigO7WneJE00b36UQkyTrftb09gDRLo"); // CHANGE THIS!
date_default_timezone_set('Europe/Istanbul');
```
## Hacking
You can add extra languages for syntax highlighting by taking the respective `mode-*` files from the https://ace.c9.io repo and placing them under src/js/lib

Note: You have to call `php src/build.php` to update the ide.php after making any changes to source files!

## Made Possible By
* https://github.com/ghedipunk/PHP-Websockets for the WebSocket server
* https://github.com/sourcelair/xterm.js/ for the terminal emulator
* https://ace.c9.io for the text editor component
