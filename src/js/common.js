function getCookie(a, b) {
    b = document.cookie.match('(^|;)\\s*' + a + '\\s*=\\s*([^;]+)');
    return b ? b.pop() : '';
}

function api(cmd, data, deferred) {
    var d = deferred? deferred : $.Deferred();
    $.ajax(window.apiUrl, { method:"post", dataType:'json', contentType:'application/json', data: JSON.stringify($.extend({}, data, {cmd: cmd})) })
        .then(function(result) {
            result = result? result : {};
            if (result.error) {
                if (result.error == "NOT_LOGGED_IN") {
                    var password =  prompt("Please enter your password.");
                    if (password) {
                    	api("login", {password: password})
                    	.then(function() {
                            api(cmd, data, d);
                        });
                    } else {
                        var msg = "API Error: " + result.error;
                        console.error(msg);
                        alert(msg);
                        d.reject(msg);
                    }
                } else {
                    var msg = "API Error: " + result.error;
                    console.error(msg);
                    alert(msg);
                    d.reject(msg);
                }
            } else {
                d.resolve(result.result);
            }
        }, function(xhr, status, err) {
            var msg = "AJAX Error: " + status + " " + xhr.responseText;
            console.error(msg);
            alert(msg);
            d.reject(msg);
        });
    return d;
}
