<?php
require_once('../htmlp/class.htmlp.php');

$htmlp = new \htmlp\HTMLP();
$htmlp->process('example.template');
$htmlp->render();