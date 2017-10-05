<form name="upload" enctype="multipart/form-data" action="index.php" method="post">
    <input type="hidden" name="MAX_FILE_SIZE" value="10485760">
    <input type="hidden" name="a" value="<?= $_REQUEST['a'] ?>">
    <input type="hidden" name="id" value="<?= $_REQUEST['id'] ?>">
    <input type="hidden" name="path" value="<?= $path ?>">
    <input type="hidden" name="mode" value="upload">
    <input type="file" accept="text/xml" id="dataFile" aria-describedby="fileHelp">
    <a class="btn btn-secondary" href="javascript:;" onclick="document.upload.submit();">Импорт</a>
    <small id="fileHelp" class="form-text text-muted">Файл в формате XML</small>
</form>