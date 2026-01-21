// Configuración general para el editor Ace
function configureAceEditor(editorId, options = {}) {
    var editor = ace.edit(editorId);

    // Configuración predeterminada
    editor.setTheme(options.theme || "ace/theme/default"); // Tema
    editor.session.setMode(options.mode || "ace/mode/sql"); // Modo (por defecto SQL)

    editor.setOptions({
        fontSize: options.fontSize || "10pt",
        showLineNumbers: options.showLineNumbers !== undefined ? options.showLineNumbers : true,
        showGutter: options.showGutter !== undefined ? options.showGutter : true,
        wrap: options.wrap || false,
        enableBasicAutocompletion: options.enableBasicAutocompletion || true,
        enableLiveAutocompletion: options.enableLiveAutocompletion || true,
        showPrintMargin: options.showPrintMargin || false,
    });

    return editor;
}
