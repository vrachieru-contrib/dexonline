<?php
require_once("../phplib/util.php");
$p = util_getRequestParameter('p');

switch($p) {
  case '404': SmartyWrap::assign('page_title', 'Pagină inexistentă'); break;
  case 'contact': SmartyWrap::assign('page_title', 'Contact'); break;
  case 'links': SmartyWrap::assign('page_title', 'Legături'); break;
  case 'ads': SmartyWrap::assign('page_title', 'Publicitate pe DEX online'); break;
  default: exit;
}

SmartyWrap::display("$p.ihtml");
?>
