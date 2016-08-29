function app_console(termPath) {

    var $console = $('#console');
    var term = new Xterm();
    //term.wraparoundMode = false;
    term.open($console[0]);
    term.fit();
    
    var updateSize = function() {
        term.fit();
    };

    api("terminal")
    .then(function(result) {
        socket = new WebSocket(result.url);
        socket.onopen = function() {
            socket.send(JSON.stringify({ijst: getCookie("ijst"), ijsh: getCookie("ijsh"), path: termPath}));
            term.attach(socket);
            $(window).resize(updateSize);
            updateSize();
            console.log("Attached to: ", socket);
        };
    });
}