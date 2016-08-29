function app_editor(filePath) {

    document.title = filePath? filePath.split('/').pop() : "New File";

    var aceEditor = ace.edit("editor");
    //aceEditor.setTheme("ace/theme/monokai");

    var setFileType = function(fp) {
        if (fp) {
            var ext = fp.split('/').pop().split('.').pop();
            console.log(ext);
            switch(ext) {
                case 'js' : aceEditor.getSession().setMode("ace/mode/javascript"); break;
                case 'php' : aceEditor.getSession().setMode("ace/mode/php"); break;
                case 'css' : aceEditor.getSession().setMode("ace/mode/css"); break;
                default:
                case 'html' : aceEditor.getSession().setMode("ace/mode/html"); break;
            }
        }
    };
    setFileType(filePath);

    /*$window.find('.window-title button.close').click(function() { 
        if (confirm("Are you sure you sure to close this tab?")) { 
            $taskButton.remove(); $window.remove(); 
        } 
    });*/

    $("#save").click(function() { 
        if (!filePath) { filePath = prompt("File path?"); }
        if (filePath) {
            api("write", {path: filePath, content: aceEditor.getValue() })
            .then(function() {
                setFileType(filePath);
                alert("Saved!");
            });
        }
    });

    if (filePath) {
        api("read", {path: filePath})
        .then(function(result) {
            aceEditor.setValue(result.contents);
        });
    }
}