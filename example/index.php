<?php
require_once('../htmlp/class.htmlp.php');

$htmlp = new \htmlp\HTMLP();
$htmlp->process(__DIR__ . '/templates/example.template');
$htmlp->render();