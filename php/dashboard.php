<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Guests are welcome (matches the landing page's "Try Without Login" promise).
// Server-side save/run features require login; the editor itself works either way.
$username = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeOven — Cloud IDE &amp; Code Sandbox</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2306b6d4'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5v-9l6 4.5-6 4.5z'/></svg>">
    
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:ital,wght@0,400;0,500;0,600;1,400&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CodeOven IDE stylesheet with cache-busting -->
    <link rel="stylesheet" href="../css/dashboard.css?v=<?php echo time(); ?>">

    <!-- CodeMirror core + themes -->
    <link rel="stylesheet" href="../codemirror/codemirror-5.65.20/lib/codemirror.css">
    <link rel="stylesheet" href="../codemirror/codemirror-5.65.20/theme/dracula.css">
    <link rel="stylesheet" href="../codemirror/codemirror-5.65.20/theme/eclipse.css">
    <link rel="stylesheet" href="../codemirror/codemirror-5.65.20/addon/dialog/dialog.css">
    <link rel="stylesheet" href="../codemirror/codemirror-5.65.20/addon/fold/foldgutter.css">
    <link rel="stylesheet" href="../codemirror/codemirror-5.65.20/addon/hint/show-hint.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <?php include 'file_expo.php'; ?>

        <div class="editor-preview-container vertical" id="editor-preview-container">
            <?php include 'editor_area.php'; ?>
            <?php include 'preview.php'; ?>
        </div>
    </div>

    <!-- CodeMirror core -->
    <script src="../codemirror/codemirror-5.65.20/lib/codemirror.js"></script>

    <!-- Language modes (order matters: htmlmixed/php depend on the modes above them) -->
    <script src="../codemirror/codemirror-5.65.20/mode/xml/xml.js"></script>
    <script src="../codemirror/codemirror-5.65.20/mode/css/css.js"></script>
    <script src="../codemirror/codemirror-5.65.20/mode/javascript/javascript.js"></script>
    <script src="../codemirror/codemirror-5.65.20/mode/htmlmixed/htmlmixed.js"></script>
    <script src="../codemirror/codemirror-5.65.20/mode/clike/clike.js"></script>
    <script src="../codemirror/codemirror-5.65.20/mode/php/php.js"></script>
    <script src="../codemirror/codemirror-5.65.20/mode/python/python.js"></script>
    <script src="../codemirror/codemirror-5.65.20/mode/sql/sql.js"></script>
    <script src="../codemirror/codemirror-5.65.20/mode/markdown/markdown.js"></script>
    <script src="../codemirror/codemirror-5.65.20/mode/shell/shell.js"></script>

    <!-- Editing addons -->
    <script src="../codemirror/codemirror-5.65.20/addon/edit/closetag.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/edit/closebrackets.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/edit/matchbrackets.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/comment/comment.js"></script>

    <!-- Code folding -->
    <script src="../codemirror/codemirror-5.65.20/addon/fold/foldcode.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/fold/foldgutter.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/fold/brace-fold.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/fold/xml-fold.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/fold/indent-fold.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/fold/comment-fold.js"></script>

    <!-- Search / replace / go to line -->
    <script src="../codemirror/codemirror-5.65.20/addon/search/searchcursor.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/search/search.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/search/jump-to-line.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/search/match-highlighter.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/dialog/dialog.js"></script>

    <!-- Autocomplete -->
    <script src="../codemirror/codemirror-5.65.20/addon/hint/show-hint.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/hint/xml-hint.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/hint/html-hint.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/hint/css-hint.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/hint/javascript-hint.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/hint/sql-hint.js"></script>
    <script src="../codemirror/codemirror-5.65.20/addon/hint/anyword-hint.js"></script>

    <!-- App logic -->
    <script>window.CODEOVEN_LOGGED_IN = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;</script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
