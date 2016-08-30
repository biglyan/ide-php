function app_editor(filePath) {

    document.title = filePath? filePath.split('/').pop() : "New File";

    var editor = ace.edit("editor");
    window.editor = editor;
    //aceEditor.setTheme("ace/theme/monokai");

    var setFileType = function(fp) {
        if (fp) {
            var ext = fp.split('/').pop().split('.').pop();
            console.log(ext);
            switch(ext) {
                case 'js' : editor.getSession().setMode("ace/mode/javascript"); break;
                case 'php' : editor.getSession().setMode("ace/mode/php"); break;
                case 'css' : editor.getSession().setMode("ace/mode/css"); break;
                default:
                case 'html' : editor.getSession().setMode("ace/mode/html"); break;
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
            api("write", {path: filePath, content: editor.getValue() })
            .then(function() {
                setFileType(filePath);
                alert("Saved!");
            });
        }
    });

    $("#findnext").click(function() {
        editor.find($('#searchtext').val());
    });

    $('#searchtext').keyup(function(e){
        if (e.keyCode == 13) { editor.find(e.target.value); }
    });

    $('#replacenext').click(function() {
        editor.replace($('#replacetext').val(), {needle: $('#searchtext').val() });
    });

    $('#replacetext').keyup(function(e){
        if (e.keyCode == 13) { editor.replace($('#replacetext').val(), {needle: $('#searchtext').val() }); }
    });

    $('#replaceall').click(function() {
        editor.replaceAll($('#replacetext').val(), {needle: $('#searchtext').val() });
    });

    $('#goto').click(function() {
        var line = parseInt(prompt("Line Number:"), 10);
        editor.scrollToLine(line, true, true, function () {});
        editor.gotoLine(line, 0, true);
    })

    if (filePath) {
        api("read", {path: filePath})
        .then(function(result) {
            editor.setValue(result.contents, -1);
        });
    }
}