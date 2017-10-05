<?php
    $filename = $_REQUEST['path'];
    $buffer = file_get_contents($filename);
    if ($buffer === false) {
        $modx->webAlertAndQuit("Error opening file for reading.");
    }
?>
	<hr>
    <div class="section" id="file_editfile" style="margin-top:1em">
        <h4><?= basename($filename) ?></h4>
        <form action="index.php" method="post" name="editFile">
            <input type="hidden" name="a" value="<?= $_REQUEST['a'] ?>">
            <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>">
            <input type="hidden" name="path" value="<?= $path ?>">
            <input type="hidden" name="mode" value="view">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td>
                        <textarea dir="ltr" name="content" readonly="readonly" id="content" class="phptextarea"><?= htmlentities($buffer, ENT_COMPAT, $modx_manager_charset) ?></textarea>
                    </td>
                </tr>
            </table>
        </form>
    </div>
 <?php
    if (false) { // CodeMirror
        $pathinfo = pathinfo($filename);
        switch ($pathinfo['extension']) {
            case "css":
                $contentType = "text/css";
                break;
            case "js":
                $contentType = "text/javascript";
                break;
            case "json":
                $contentType = "application/json";
                break;
            case "php":
                $contentType = "application/x-httpd-php";
                break;
            default:
                $contentType = 'htmlmixed';
        };
        $evtOut = $modx->invokeEvent('OnRichTextEditorInit', array(
            'editor' => 'Codemirror',
            'elements' => array(
                'content',
            ),
            'contentType' => $contentType,
            'readOnly' => true
        ));
        if (is_array($evtOut)) {
            echo implode('', $evtOut);
        }
    }
?>