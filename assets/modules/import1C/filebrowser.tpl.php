<div id="ManageFiles">
    <h3><i class="fa fa-folder-open-o"></i>&nbsp;/<?= ltrim($path, '/') ?></h3>
    <div class="table-responsive">
        <table id="FilesTable" class="table data">
            <thead>
                <tr>
                    <th>Имя</th>
                    <th style="width:1%">Дата</th>
                    <th style="width:1%">Размер</th>
                    <th style="width:1%">Тип</th>
                    <th style="width:1%"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($dir as $item): ?>
                <tr>
                    <td>
                        <?php if($item['type'] == 'Каталог' || $item['name'] == '..') : ?>
                        <i class="fa <?= ($item['name'] == '..' ? 'fa-folder-open-o' : 'fa-folder-o') ?>"></i>
                        <a href="index.php?a=<?= $_REQUEST['a'] ?>&id=<?= $_REQUEST['id'] ?>&path=<?= urlencode($item['path']) ?>"><b><?= $item['name'] ?></b></a>
                        <?php else: ?>
                        <i class="fa <?= ($_REQUEST['mode']=='view' && basename($_REQUEST['path']) == $item['name'] ? 'fa-eye' : 'fa-file-o')?>"></i>
                        <span style="cursor:help" title="<?= xmlType($item['name']) ?>"><?= $item['name'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap"><?= $item['time'] ?></td>
                    <td style="white-space:nowrap;text-align:right;"><?= $item['size'] ?></td>
                    <td style="white-space:nowrap"><?= $item['type'] ?></td>
                    <td style="white-space:nowrap">
                        <?php if($item['type'] != 'Каталог' && $item['name'] != '..') : ?>
                        <a href="index.php?a=<?= $_REQUEST['a'] ?>&id=<?= $_REQUEST['id'] ?>&path=<?= urlencode($item['path']) ?>&mode=view" title="Просмотр" onclick="parent.document.getElementById('mainloader').classList.add('show');"><i class="fa fa-eye"></i></a>
                        <a href="index.php?a=<?= $_REQUEST['a'] ?>&id=<?= $_REQUEST['id'] ?>&path=<?= urlencode($item['path']) ?>&mode=import" title="Импорт" onclick="if (confirm('Загрузить данные?')) { parent.document.getElementById('mainloader').classList.add('show'); } else {return false;}"><i class="fa fa-download"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>