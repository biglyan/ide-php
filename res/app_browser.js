function app_browser(openPath) {

    var path = openPath? openPath : ".";
   
    $browser = $('#browser');

    var browseTo = function(targetPath) {

        api("browse", {path: targetPath})
        .then(function(contents) {
            path = contents.path;
            document.title = contents.basename;

            var html = '<div class="browser-item folder-item"><span class="item-name">..</span></div>';
            html += contents.folders.map(function(dir) { 
                return '\
                <div class="browser-item folder-item"><span class="item-name">'+ dir +'</span>\
                <a target="_blank" href="?console=' + path + '/' + dir + '"><img src="res/console.png"/></a></div>\
                ';
            }).join("");
            html += contents.files.map(function(file) {
                return '\
                <div class="browser-item file-item"><span class="item-name">'+ file +'</span>\
                <a target="_blank" href="?editor=' + path + '/' + file + '"><img src="res/editor.png"/></a></div>\
                ';
            }).join("");
            
            $browser.html(html);

            $browser.find('.folder-item .item-name').click(function() {
                var folderPath = path + "/" + $(this).text();
                console.log(folderPath);
                browseTo(folderPath);
            });

            $browser.find('.file-item .item-name').click(function() {
                var filePath = path + "/" + $(this).text();
                console.log(filePath);
                openFile(filePath);
            });

        });
    }

    browseTo(path);
}