<?php
require_once("../../phplib/util.php"); 
util_assertModerator(PRIV_STRUCT);
util_assertNotMirror();

// Select entries that
// * are associated with definitions from DEX '98 or DEX '09
// * have the shortest total definition length (from all sources)
$entries = Model::factory('Entry')
         ->table_alias('e')
         ->select('e.*')
         ->join('EntryDefinition', 'e.id = ed.entryId', 'ed')
         ->join('Definition', 'ed.definitionId = d.id', 'd')
         ->where('e.structStatus', Entry::STRUCT_STATUS_NEW)
         ->where_not_equal('d.status', Definition::ST_DELETED)
         ->group_by('e.id')
         ->having_raw('sum(sourceId in (1, 27)) > 0')
         ->having_raw('sum(length(internalRep)) < 300')
         ->limit(100)
         ->find_many();

// Load the definitions for each lexem
$searchResults = [];
foreach ($entries as $e) {
  $defs = Definition::loadByEntryId($e->id);
  $searchResults[] = SearchResult::mapDefinitionArray($defs);
}

SmartyWrap::assign('entries', $entries);
SmartyWrap::assign('searchResults', $searchResults);
SmartyWrap::assign('recentLinks', RecentLink::loadForUser());
SmartyWrap::displayAdminPage('admin/structChooseEntry.tpl');

?>
