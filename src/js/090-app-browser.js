function app_browser(openPath) {

    var path = openPath? openPath : ".";
   
    $browser = $('#browser');

    var browseTo = function(targetPath) {

        api("browse", {path: targetPath})
        .then(function(contents) {
            path = contents.path;
            document.title = contents.basename;

            var html = '\
            <tr class="browser-item folder-item">\
                <td class="name">..</td>\
                <td class="buttons">\
                    <a target="_blank" href="?console=."><img src="?png=console.png"/></a>\
                </td>\
            </tr>';

            html += contents.folders.map(function(dir) { 
                return '\
                <tr class="browser-item folder-item">\
                    <td class="name">'+ dir +'</td>\
                    <td class="buttons">\
                        <a target="_blank" href="?console=' + path + '/' + dir + '"><img src="?png=console.png"/></a>\
                        <a href="#" onclick="if (confirm(\'Are you sure?\')) api(\'delete\', {path: \'' + path + '/' + dir + '\'}).then(browseTo.bind(null, \''+path+'\'))"><img src="?png=delete.png"/></a>\
                    </td>\
                </tr>';
            }).join("");

            html += contents.files.map(function(file) { 
                return '\
                <tr class="browser-item file-item">\
                    <td class="name">'+ file +'</td>\
                    <td class="buttons">\
                        <a target="_blank" href="?editor=' + path + '/' + file + '"><img src="?png=editor.png"/></a>\
                        <a href="#" onclick="if (confirm(\'Are you sure?\')) api(\'delete\', {path: \'' + path + '/' + file + '\'}).then(browseTo.bind(null, \''+path+'\'))"><img src="?png=delete.png"/></a>\
                    </td>\
                </tr>';
            }).join("");
            
            $browser.html(html);

            $browser.find('.folder-item .name').click(function() {
                var folderPath = path + "/" + $(this).text();
                console.log(folderPath);
                browseTo(folderPath);
            });

            $browser.find('.file-item .name').click(function() {
                var filePath = path + "/" + $(this).text();
                console.log(filePath);
                window.open("?download="+filePath, "_blank");
            });

        });
    }

    window.browseTo = browseTo;
    browseTo(path);

    $('#file').change(function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = function (evt) {
                api("write_data_url", { path: path + "/" + file.name, content: evt.target.result })
                .then(function() {
                    alert("File saved!");
                    browseTo(path);
                });
            }
            reader.onerror = function (evt) {
                alert("Error loading file!");
            }
        }
    });

    $('#home').click(function() {
        browseTo(".");
    });

    $('#upload').click(function() {
        $('#file').click();
    });

    $('#newfile').click(function() {
        var filePath = path + "/" + prompt("Enter file name:");
        window.open("?editor=" + filePath, '_blank').focus();
    });
}