<?php
/*** CONFIGURE THIS APP BELOW ***/

/*	 DOMAIN, DIRECTORY AND DATABASE
 * 	 url - Here you should add your site public url without protocol and without trailing slash, e.g. mysite.com 
 *   dir - The name of the app directory in /app/ folder. e.g. mysite
 *   db - The database configuration of the app
 *   timezone - The timezone for the app. Should be one of the specified here: http://php.net/manual/en/timezones.php
 *   template - Accepts boolean or string. Specifies the name of the template you want to use. The template name should be a folder in /view/template/. If set to false, it will not use a template.
 *   smart_elements - Accepts boolean. If true, the response will parse all smart elements. A smart element is e.g. [widget:breadcrumb]
 *   debug_mode - Accepts integer 0 and 1. If set to 1, you will see debug information at the bottom of your page. 
 */

$config[] = array(
    'url'=>array('localhost/mvc-x'),
    'dir'=>'hellotars',
    'db' => array(
        'type'=>'mysql',
        'host'=>'localhost',
        'name'=>'',
        'username'=>'',
        'password'=>'',
        'table_prefix'=>''
    ),
    'timezone' => 'Europe/Sofia',
    'template'=> false,
    'smart_elements'=> true,
    'debug_mode'=> 1
);



