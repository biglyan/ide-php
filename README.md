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
 0. Edit the settings in the first lines of the ide.php file to suit your needs,
 0. use `php ide.php password <password>` command to set a login password

and you are ready to go!

## Hacking
You can add extra languages for syntax highlighting by taking the respective `mode-*` files from the https://ace.c9.io repo and placing them under src/js/lib

Note: You have to call src/build.php at src folder to update the ide.php after making any changes to source files!

## Made Possible By
* https://github.com/ghedipunk/PHP-Websockets for the WebSocket server
* https://github.com/sourcelair/xterm.js/ for the terminal emulator
* https://ace.c9.io for the text editor component
