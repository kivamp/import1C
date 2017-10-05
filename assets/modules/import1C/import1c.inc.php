<?php
/**
 *
 * @name 1C data import module for Shopleeper / Evo
 * @version 1.0
 * @author k4& <kivamp@gmail.com>
 *
 */

defined('IN_MANAGER_MODE') or die();

define("XML1C", "2.08");
define("PARENT", 31);
define("IMG_PATH", "assets/images/market");

date_default_timezone_set('Europe/Moscow');
setlocale (LC_ALL, 'ru_RU.UTF-8');

global $template, $counter;

include_once 'tv.class.php';

$tv = array(
    'Id' => 'Идентификатор',
    'Article' => 'Артикул',
    'Код' => 'Code',
    'Image' => 'Изображение',
    'Price' => 'Цена',
    'Rest' => 'Остаток',
    'Country' => 'Страна'
);

$mod = array(
    'title' => "Импорт данных из 1С",
    'url' => "index.php?a=112&id=".$_GET['id'],
    'path' => "../assets/modules/import1C/"
);


if( isset($_REQUEST['path']) && !empty($_REQUEST['path']) ) {
    $path = str_replace('..', '', $_REQUEST['path']);
    if( is_file($modx->config['filemanager_path'] . $path) &&
        $_REQUEST['mode'] == 'import' ) {
        $counter = 0;
        $res = $modx->db->select("template", $modx->getFullTableName('site_content'),  "id=" . PARENT);
        if( $modx->db->getRecordCount($res) ) {  // Родитель найден, сохраним номер шаблона
            $template = $modx->db->getValue($res);
            $log = importData($path);
        } else {
            $modx->webAlertAndQuit("Родительская категория " . PARENT . " не найдена!");
        }
        $path = removeLastPath($path);
    }
} else {
    $path = "";
}

$path = rtrim($path, '/');

$dir = prepareDirTree($path);

require $mod['path']."index.tpl.php";

//---------------------------------------------------------------------------------------------------------------------
function prepareDirTree($path) {
    global $modx;

    $realpath = $modx->config['filemanager_path'] . $path;
    if( !is_readable($realpath) ) {
        $modx->webAlertAndQuit($_lang["not_readable_dir"]);
    }

    $dir = scandir($realpath);

    $files = array();
    $dirs = array();
    if( !empty($path)) {
        $dirs[] = array(
            'name' => '..',
            'path' => removeLastPath($path),
            'time' => '',
            'size' => '',
            'type' => ''
        );
    }
    foreach( $dir as $file ) {
        if( substr($file, 0, 1) === '.' ) {
            continue;
        }
        $fullpath = $realpath . '/' . $file;
        $size = formatBytes(filesize($fullpath));
        $time = filemtime($fullpath);
        $time = date("d.m.Y H:i:s", $time);
        if( is_dir($fullpath) ) {
            $dirs[] = array(
                'name' => $file,
                'path' => $path . '/' . $file,
                'time' => $time,
                'size' => '',
                'type' => 'Каталог'
            );
        } else {
            $mime = mime_content_type($fullpath);
            if( $mime !== 'application/xml' ) {
                continue;
            }
            $files[] = array(
                'name' => $file,
                'path' => $path . '/' . $file,
                'time' => $time,
                'size' => $size,
                'type' => $mime
            );
        }
    }
    return array_merge($dirs, $files);
}
//---------------------------------------------------------------------------------------------------------------------
function formatBytes($bytes, $precision = 2) {
    $units = array('b', 'Kb', 'Mb', 'Gb', 'Tb');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);
    // OR $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}
//---------------------------------------------------------------------------------------------------------------------
function removeLastPath($string) {
    $pos = strrpos($string, '/');
    if( $pos !== false ) {
        $path = substr($string, 0, $pos);
    } else {
        $path = false;
    }
    return $path;
}
//---------------------------------------------------------------------------------------------------------------------
function importData($path) {
    global $modx, $counter;

    $path = $modx->config['filemanager_path'] . $path;

    if( file_exists($path) ) {
        $xml = simplexml_load_file($path);
        if( !isset($xml['ВерсияСхемы']) || $xml['ВерсияСхемы'] != XML1C ) {
            return msg('Файл: '.$path.' не является выгрузкой версии ' . XML1C, 'danger');
        }
        // Свойства, Остатки, Цены
        if(  isset($xml->{ПакетПредложений}) &&
             isset($xml->{ПакетПредложений}->{Предложения}) &&
             isset($xml->{ПакетПредложений}->{Предложения}) ) {
            $out = loadProperties($xml->{ПакетПредложений}->{Предложения}->{Предложение}, $path);
            return msg('Загружено свойств: '.$counter) . $out;
        } else {
            // Товары
            if(  isset($xml->{Каталог}) &&
                 isset($xml->{Каталог}->{Товары}) &&
                 isset($xml->{Каталог}->{Товары}->{Товар}) ) {
                $out = loadItems($xml->{Каталог}->{Товары}->{Товар}, $path);
                return msg('Загружено товаров: '.$counter) . $out;
            }
            // Категории
            if(  isset($xml->{Классификатор}) &&
                 isset($xml->{Классификатор}->{Группы}) &&
                 isset($xml->{Классификатор}->{Группы}->{Группа}) ) {
                $out = loadCategories($xml->{Классификатор}->{Группы}->{Группа});
                return msg('Загружено категорий: '.$counter) . $out;
            }
        }
    } else {
        return msg('Не удалось открыть файл: '.$path, 'danger');
    }
    return msg('Что-то пошло не так!', 'danger');
}
//---------------------------------------------------------------------------------------------------------------------
function loadProperties($xml, $path) {
    global $modx, $counter, $template;

    // return msg($path, 'dump');

    $table = $modx->getFullTableName('catalog');
    $tvId = new TV('Id', 'Идентификатор');
    $tvId->setSource('catalog_tmplvar_contentvalues');
    $tvPrice = new TV('Price', 'Цена');
    $tvPrice->setSource('catalog_tmplvar_contentvalues');
    $tvRest = new TV('Rest', 'Остаток');
    $tvRest->setSource('catalog_tmplvar_contentvalues');

    $out = '<ul>';
    foreach( $xml as $item ) {
        if( !isset($item->{Ид}) ) {
            continue;
        }
        if( isset($item->{Цены}) ) { // ВРЕМЕННО! Или цена или остаток, возможно бывает и то и другое. Требует уточнения.
            $tv = $tvPrice;
            $val = (string) $item->{Цены}->{Цена}->{ЦенаЗаЕдиницу};
        } elseif( isset($item->{Остатки}) ) {
            $tv = $tvRest;
            $val = (string) $item->{Остатки}->{Остаток}->{Количество};
        } else {
            continue;
        }

        $cid = (string) $item->{Ид};
        $id = $tvId->getByValue($cid); // Ищем товар по идентификатору
        if( $id ) { // Товар найден, проверяем наличие в основной таблице
            $res = $modx->db->select("pagetitle", $table,  "id={$id}");
            if( $modx->db->getRecordCount($res) ) {  // Товар найден, обновить
                $title = $modx->db->getValue($res);
            } else {  // Товар не найден
                continue;
            }
        } else {
            continue;
        }

        //Устанавливаем значение
        $tv->setValue($id, $val);

        $counter++;
        $out .= sprintf('<li id="%s">%s : <small><b>%s = %s</b></small><div></li>', $id, $title, $tv->caption, $val);
    }
    $out .= '</ul>';
    return $out;
}
//---------------------------------------------------------------------------------------------------------------------
function loadItems($xml, $path) {
    global $modx, $counter, $template;

    $path = removeLastPath($path);

    // return msg($path, 'dump');

    $table = $modx->getFullTableName('catalog');
    $tvContentId = new TV('Id', 'Идентификатор');
    $tvCatalogId = new TV('Id', 'Идентификатор');
    $tvCatalogId->setSource('catalog_tmplvar_contentvalues');
    $tvArticle = new TV('Article', 'Артикул');
    $tvArticle->setSource('catalog_tmplvar_contentvalues');
    $tvCountry = new TV('Country', 'Страна');
    $tvCountry->setSource('catalog_tmplvar_contentvalues');
    $tvImage = new TV('Image', 'Изображение');
    $tvImage->setSource('catalog_tmplvar_contentvalues');
    $tvCode = new TV('Code', 'Код');
    $tvCode->setSource('catalog_tmplvar_contentvalues');

    $out = '<ul>';
    foreach( $xml as $item ) {
        if( !isset($item->{Ид}) || !isset($item->{Наименование}) || !isset($item->{Группы}->{Ид}) ) {
            continue;
        }

        $parent = $tvContentId->getByValue((string) $item->{Группы}->{Ид}); // Ищем категорию по идентификатору

        if(!$parent) { // Категория не найдена, пропустить товар
            continue;
        }

        $attr = array();
        if( isset($item->{ЗначенияРеквизитов}->{ЗначениеРеквизита}) ) {
            foreach( $item->{ЗначенияРеквизитов}->{ЗначениеРеквизита} as $r ) {
                $attr[(string)$r->{Наименование}] = (string) $r->{Значение};
            }
        }

        $cid = (string) $item->{Ид};
        $title = $modx->db->escape((string) $item->{Наименование});
        $published = ((string) $item->{ПометкаУдаления} == 'true' ? 0 : 1);
        $event = $modx->invokeEvent('OnStripAlias', array('alias' => $title));
        $alias = is_array($event) ?  implode('', $event) : '';
        $content = isset($item->{Описание}) ? $modx->db->escape(nl2br((string) $item->{Описание})) : '';
        $introtext = isset($attr['Полное наименование']) ? $modx->db->escape($attr['Полное наименование']) : '';
        $fields = array(
            'pagetitle' => $title,
            'alias' => $alias,
            'published' => $published,
            'parent' => $parent,
            'isfolder' => 0,
            'introtext' => $introtext,
            'content' => $content,
            'template' => $template
        );

        $id = $tvCatalogId->getByValue($cid); // Ищем товар по идентификатору

        if( $id ) { // Товар найден, проверяем наличие в основной таблице
            $res = $modx->db->select("id", $table,  "id={$id}");
            if( $modx->db->getRecordCount($res) ) {  // Товар найден, обновить
                $result = $modx->db->update($fields, $table, 'id = ' . $id);
                $action = $result ? 'Обновлено' : 'Ошибка';
            } else {  // Товар не найден
                $id = false;
            }
        }
        if( !$id ) {  // Товар не найден, добавить
            $fields['createdon'] = time();
            $id = $modx->db->insert($fields, $table);
            $action = $id ? 'Добавлено' : 'Ошибка';
        }

        //Заполняем TV параметры
        $tvCatalogId->setValue($id, $cid); // Обновляем внутренний код

        if( !empty($item->{Артикул}) ) {
            $tvArticle->setValue($id, (string) $item->{Артикул});
        }

        if( !empty($item->{Страна}) ) {
            $tvCountry->setValue($id, (string) $item->{Страна});
        }

        if( !empty($item->{Картинка}) ) {
            $img = (string) $item->{Картинка};
            $srcFile = $path . "/" . $img;
            $img = basename($img);
            $dstPath = $modx->config['filemanager_path'] . "assets/images/market/" . $id;
            if( is_dir($dstPath) === FALSE) {
                if( mkdir($dstPath, 0775, true) === FALSE)  {
                    return msg('Не возможно создать каталог ' . "assets/images/market/" . $id , 'danger');
                }
            }
            $dstFile = $dstPath. "/" . $img;
            if( file_exists($srcFile) ) {
        		if( file_exists($dstFile) && filesize($srcFile) != filesize($dstFiles) ) {
                    unlink($dstFile);
                }
                if( !file_exists($dstFile) ) {
                    copy($srcFile, $dstFile);
                }
                $tvImage->setValue($id, "assets/images/market/" . $id . "/" . $img);
            }
        }

        if( !empty($attr['Код']) ) {
            $tvCode->setValue($id, $attr['Код']);
        }

        $counter++;
        $out .= sprintf('<li id="%s" style="%s">%s : <small><b>%s</b></small><div></li>', $cid, ($deleted ? 'text-decoration:line-through' : ''), $title, $action);
    }
    $out .= '</ul>';
    return $out;
}
//---------------------------------------------------------------------------------------------------------------------
function loadCategories($xml, $parent = PARENT) {
    global $modx, $template, $counter;

    $table = $modx->getFullTableName('site_content');
    $tvId = new TV('Id', 'Идентификатор');

    $out = '<ul>';
    foreach ($xml as $item) {
        if( !isset($item->{Ид}) || !isset($item->{Наименование}) ) {
            continue;
        }

        $cid = (string) $item->{Ид};
        $published = ((string) $item->{ПометкаУдаления} == 'true' ? 0 : 1);
        $title = $modx->db->escape((string) $item->{Наименование});
        $event = $modx->invokeEvent('OnStripAlias', array('alias' => $title));
        $alias = is_array($event) ?  implode('', $event) : '';
        $fields = array(
            'type' => 'document',
            'contentType' => 'text/html',
            'parent' => $parent,
            'isfolder' => 1,
            'template' => $template,
            'cacheable' => 1,
            'pagetitle' => $title,
            'alias' => $alias,
            'published' => $published
        );

        $id = $tvId->getByValue($cid);

        if( $id ) { // Категория найдена, проверяем в основной таблице
            $res = $modx->db->select("id", $table,  "id={$id}");
            if( $modx->db->getRecordCount($res) ) {  // Категория найдена, обновить
                $fields['editedon'] = time();
                $fields['editedby'] = $modx->getLoginUserID();
                $result = $modx->db->update($fields, $table, 'id = ' . $id);
                $action = $result ? 'Обновлено' : 'Ошибка';
            } else {
                $id = false;
            }
        }
        if( !$id ) { // Необходимо добавить категорию
            $fields['createdon'] = time();
            $fields['createdby'] = $modx->getLoginUserID();
            $id = $modx->db->insert($fields, $table);
            $action = $id ? 'Добавлено' : 'Ошибка';
        }

        $tvId->setValue($id, $cid); // Обновляем внутренний код

        $counter++;
        $out .= sprintf('<li id="%s" style="%s">%s : <small><b>%s</b></small><div></li>', $cid, ($published ? '' : 'text-decoration:line-through'), $title, $action);
        if( isset($item->{Группы}) &&
            isset($item->{Группы}->{Группа}) ) {
            $out .= loadCategories($item->{Группы}->{Группа}, $id);
        }
    }
    $out .= '</ul>';
    return $out;
}
//---------------------------------------------------------------------------------------------------------------------
function msg($msg, $type="info") {
    // success, info, warning, danger, dump
    if( $type == 'dump' )
        return '<pre>'.print_r($msg, true).'</pre>';
    else
        return '<h4 class="alert alert-' . $type . '">' . $msg . '</h4>';
}
//---------------------------------------------------------------------------------------------------------------------
function xmlType($name) {
    if( substr($name, 0, 9) == 'import___' ) return 'Каталог';
    if( substr($name, 0, 9) == 'prices___' ) return 'Цены';
    if( substr($name, 0, 8) == 'rests___' ) return 'Остатки';
    if( substr($name, 0, 9) == 'offers___' ) return 'Свойства';
    return '';
}
//---------------------------------------------------------------------------------------------------------------------
?>
