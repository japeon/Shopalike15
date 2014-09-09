<?php

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/shopalikexml.php');

$module=new ShopALikeXML();
$module->generateFileList();
die ('OK');

?>
