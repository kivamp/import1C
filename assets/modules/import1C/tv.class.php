<?php
class TV {

    private $templateid;   // Template id
    private $data;
    public $name;
    public $caption;
    public $description;
    public $type;
    public $id;

    function __construct($name, $caption = '', $description = '', $type = 'text') {
        global $modx, $template;

        $this->setSource();
        $this->setTemplateId($template);

        $this->name = $name;
        $this->caption = $caption;
        $this->description = $description;
        $this->type = $name;

        $id = $this->check();
        if (!$id) {
            $id = $this->create();
        }
        $this->id = $id;
    }

    function setSource($table = 'site_tmplvar_contentvalues') {
        global $modx;

        $this->data = $modx->getFullTableName($table);
    }

    function setTemplateId($templateid = 0) {
        $this->templateid = $templateid;
    }

    function check() {
        global $modx;

        $res = $modx->db->select("id", $modx->getFullTableName('site_tmplvars'),  "name='{$this->name}'");
        if ($modx->db->getRecordCount($res)) return $modx->db->getValue($res);
        return false;
    }

    function create() {
        global $modx;

        $fields = array(
            'type'          => $this->type,
            'name'          => $this->name,
            'caption'       => $modx->db->escape($caption),
            'description'   => $modx->db->escape($this->description),
            'createdon'     => time()
        );
        $tv = $modx->db->insert($fields, $this->tvs);
        if ($tv) {
            $this->template($tv, $this->tid);
        }
        return $tv;
    }

    function template($tv, $templateid = false) {
        global $modx;

        $table = $modx->getFullTableName('site_tmplvar_templates');
        if ($templateid) {
            $modx->db->insert(array('tmplvarid' => $tv, 'templateid' => $templateid), $table);
        } else {
            $res = $modx->db->select("templateid", $table,  "`tmplvarid`={$tv}");
            if ($modx->db->getRecordCount($res)) {
                $tpls = array();
                while ( $row = $modx->db->getRow($res) ) {
                    $tpls[] = $row['templateid'];
                }
            } else {
                return false;
            }
        }
    }

    function getById($id) {
        global $modx;

        $res = $modx->db->select("value", $this->data,  "`tmplvarid`={$this->id} AND `contentid`={$id}");
        if ($modx->db->getRecordCount($res)) return $modx->db->getValue($res);
        return false;
    }

    function getByValue($val) {
        global $modx;

        $res = $modx->db->select("contentid", $this->data,  "`tmplvarid`={$this->id} AND `value`='{$val}'");
        if ($modx->db->getRecordCount($res)) return $modx->db->getValue($res);
        return false;
    }

    function setValue($id, $val) {
        global $modx;

        $modx->db->delete($this->data, "`tmplvarid` = {$this->id} AND `contentid` = {$id}"); // Очистить данные
        $modx->db->insert(array('tmplvarid' => $this->id, 'contentid' => $id, 'value' => $modx->db->escape($val)), $this->data);
    }

}
?>