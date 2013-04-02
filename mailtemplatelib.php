<?php

/**
* @package mod-tracker
* @category mod
* @author Valery Fremaux / 1.8
* @date 02/12/2007
*
* Library of functions for mail templating
*/

// protects against possible multiple inclusions
if (!function_exists('compile_mail_template')){

/**
* useful templating functions from an older project of mine, hacked for Moodle
* @param template the template's file name from $CFG->sitedir
* @param infomap a hash containing pairs of parm => data to replace in template
* @return a fully resolved template where all data has been injected
*/
function compile_mail_template($template, $infomap, $module = 'tracker', $lang = '') {
    $notification = implode('', get_mail_template($template, $module, $lang));
    foreach($infomap as $aKey => $aValue){
        $notification = str_replace("<%%$aKey%%>", $aValue, $notification);
    }
    return $notification;
}

/*
* resolves and get the content of a Mail template, acoording to the user's current language.
* @param virtual the virtual mail template name
* @param module the current module
* @param lang if default language must be overriden
* @return string the template's content or false if no template file is available
*/
function get_mail_template($virtual, $modulename, $lang = ''){
   global $CFG, $USER;

    if ($lang == '') {
        $lang = current_language();
    }
    if (preg_match('/^auth_/', $modulename)){
        $location = 'auth';
        $modulename = str_replace('auth_', '', $modulename);
    } elseif (preg_match('/^block_/', $modulename)){
        $location = 'blocks';
        $modulename = str_replace('block_', '', $modulename);
    } else {
        $location = 'mod';
    }
    $templateName = "{$CFG->dirroot}/{$location}/{$modulename}/mails/{$lang}/{$virtual}.tpl";
    if (file_exists($templateName))
      return file($templateName);
    notice("template $templateName not found");
    return array();
}

//Multiple inclusions
}
?>