<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

/* crmv@208173 */

class VTWorkflowTemplateManager{
    public function __construct($adb){
        $this->adb = $adb;
    }

    /**
     * Create anew template instance from a workflow
     *
     * This template instance will not be saved. The save
     * will have to be done explicitly.
     *
     * @param $title The title of the template
     * @param $workflow A workflow instance.
     */
    public function newTemplate($title, $workflow){
        $adb = $this->adb;
        $wms = new VTWorkflowManager($adb);
        $str = $wms->serializeWorkflow($workflow);
        $template = new VTWorkflowTemplate();
        $template->moduleName = $workflow->moduleName;
        $template->title = $title;
        $template->template = $str;
        return $template;
    }

    /**
     * Retrieve a template given it's id
     *
     * @param $templateId The id of the template
     * @return The template object
     */
    public function retrieveTemplate($templateId){
        $adb = $this->adb;
        global $table_prefix;
        $result = $adb->pquery('select * from com_'.$table_prefix.'_workflowtemplates where template_id=?', array($templateId));
        $it = new SqlResultIterator($adb, $result);
        $data = $it->current();
        $template = new VTWorkflowTemplate();
        $template->id = $templateId;
        $template->title = $data->title;
        $template->moduleName = $data->module_name;
        $template->template = $data->template;
        return $template;
    }

    /**
     * Create a workflow from a template
     *
     * The new workflow will also be added to the database.
     *
     * @param $template The template to use
     * @return A workflow object.
     */
    public function createWorkflow($template){
        $adb = $this->adb;
        $wfm = new VTWorkflowManager($adb);
        return $wfm->deserializeWorkflow($template->template);
    }

    /**
     * Get template objects for a particular module.
     *
     * @param $moduleName The name of the module
     * @return An array containing template objects
     */
    public function getTemplatesForModule($moduleName){
        $adb = $this->adb;
        global $table_prefix;
        $result = $adb->pquery("select * from com_".$table_prefix."_workflowtemplates where module_name=?", array($moduleName));
        return $this->getTemplatesForResult($result);
    }

    /**
     * Get all templates
     *
     * Get all the templates as an array
     *
     * @return An array containing template objects.
     */
    public function getTemplates(){
        $adb = $this->adb;
        global $table_prefix;
        $result = $adb->query("select * from com_".$table_prefix."_workflowtemplates");
        return $this->getTemplatesForResult($result);
    }

    /**
     * Load templates form a dumped string
     *
     * @param $str The string dump generated from dumpAllTemplates
     */
    public function loadTemplates($str){
        $arr = Zend_Json::decode($str);
        foreach($arr as $el){
            $template = new VTWorkflowTemplate();
            $template->moduleName = $el['moduleName'];
            $template->title = $el['title'];
            $template->template = $el['template'];
            $this->save($template);
            $this->createWorkflow($template);
        }
    }


    private function getTemplatesForResult($result){
        $adb = $this->adb;
        $it = new SqlResultIterator($adb, $result);
        $templates = [];
        foreach($it as $row){
            $template = new VTWorkflowTemplate();
            $template->id = $row->template_id;
            $template->title = $row->title;
            $template->moduleName = $row->module_name;
            $template->template = $row->template;
            $templates[] = $template;
        }
        return $templates;
    }

    /**
     * Save a template
     *
     * If the object is a newly created template it
     * will be added to the database and a field id containing
     * the new id will be added to the object.
     *
     * @param $template The template object to save.
     */
    public function saveTemplate($template){
        $adb = $this->adb;
        global $table_prefix;
        if(is_numeric($template->id)){//How do I check whether a member exists in php?
            $templateId = $template->id;
            $adb->pquery("update com_{$table_prefix}_workflowtemplates set title=?," .
                " module_name=?, template=? where template_id=?",
                array($template->title, $template->moduleName,
                    $template->template, $templateId));
            return $templateId;
        }else{
            $templateId = $adb->getUniqueID("com_".$table_prefix."_workflowtemplates");
            $template->id = $templateId;
            $adb->pquery("insert into com_{$table_prefix}_workflowtemplates 
							(template_id, title, module_name, template) 
							values (?, ?, ?, ?)",
                array($templateId, $template->title,
                    $template->moduleName, $template->template));
            return $templateId;
        }
    }

    /**
     * Delete a template
     *
     * $templateId The id of the template to delete.
     */
    public function deleteTemplate($templateId){
        $adb = $this->adb;
        global $table_prefix;
        $adb->pquery('delete from com_'.$table_prefix.'_workflowtemplates where template_id=?',
            array($templateId));
    }


    /**
     * Dump all the templates in vte into a string
     *
     * This can be used for exporting templates from one
     * machine to another
     *
     * @return The string dump of the templates.
     */
    public function dumpAllTemplates(){
        $adb = $this->adb;
        global $table_prefix;
        $result = $adb->query("select * from com_{$table_prefix}_workflowtemplates");
        $it = new SqlResultIterator($adb, $result);
        $arr = [];
        foreach($it as $row){
            $el = [
                'moduleName'=>$row->module_name,
                'title'=>$row->title,
                'template'=>$row->template
            ];
            $arr[] = $el;
        }
        return Zend_Json::encode($arr);
    }
}

class VTWorkflowTemplate{

}