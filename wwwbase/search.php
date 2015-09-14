<?php

require_once("../phplib/util.php");
require_once("../phplib/ads/adsModule.php");

$cuv = util_getRequestParameter('cuv');
$lexemId = util_getRequestParameter('lexemId');
$defId = util_getRequestParameter('defId');
$sourceUrlName = util_getRequestParameter('source');
$text = util_getRequestIntParameter('text');
$showParadigm = util_getRequestParameter('showParadigm');
$xml = util_getRequestParameter('xml');
$all = util_getRequestParameter('all');


$redirect = session_get('redirect');
$redirectFrom = session_getWithDefault('init_word', '');
session_unsetVariable('redirect');
session_unsetVariable('init_word');

if ($cuv && !$redirect) {
  $cuv = StringUtil::cleanupQuery($cuv);
}

util_redirectToFriendlyUrl($cuv, $lexemId, $sourceUrlName, $text, $showParadigm, $xml, $all);

$searchType = SEARCH_INFLECTED;
$hasDiacritics = session_user_prefers(Preferences::FORCE_DIACRITICS);
$exclude_unofficial = session_user_prefers(Preferences::EXCLUDE_UNOFFICIAL);
$hasRegexp = FALSE;
$isAllDigits = FALSE;
$showParadigm = $showParadigm || session_user_prefers(Preferences::SHOW_PARADIGM);
$all = $all || $showParadigm;
SmartyWrap::assign('allDefinitions', $all);

$paradigmLink = $_SERVER['REQUEST_URI'] . ($showParadigm ? '' : '/paradigma');
$source = $sourceUrlName ? Source::get_by_urlName($sourceUrlName) : null;
$sourceId = $source ? $source->id : null;

if ($cuv) {
  SmartyWrap::assign('cuv', $cuv);
  $arr = StringUtil::analyzeQuery($cuv);
  $hasDiacritics = session_user_prefers(Preferences::FORCE_DIACRITICS) || $arr[0];
  $hasRegexp = $arr[1];
  $isAllDigits = $arr[2];
}

if ($isAllDigits) {
  $d = Definition::get_by_id($cuv);
  if ($d) {
    util_redirect(util_getWwwRoot() . "definitie/{$d->lexicon}/{$d->id}" . ($xml ? '/xml' : ''));
  }
}

if ($text) {
  $searchType = SEARCH_FULL_TEXT;
  if (Lock::exists(LOCK_FULL_TEXT_INDEX)) {
    SmartyWrap::assign('lockExists', true);
    $definitions = array();
  } else {
    $words = preg_split('/ +/', $cuv);
    list($defIds, $stopWords) = Definition::searchFullText($words, $hasDiacritics, $sourceId);
    SmartyWrap::assign('numResults', count($defIds));
    SmartyWrap::assign('stopWords', $stopWords);
    // Show at most 500 definitions;
    $defIds = array_slice($defIds, 0, LIMIT_FULLTEXT_DISPLAY);
    // Load definitions in the given order
    $definitions = array();
    foreach ($defIds as $id) {
      $definitions[] = Definition::get_by_id($id);
    }
    if (!count($defIds)) {
      FlashMessage::add('Nicio definiție nu conține toate cuvintele căutate.');
    }
    Definition::highlight($words, $definitions);
  }
  $searchResults = SearchResult::mapDefinitionArray($definitions);
}

// LexemId search
if ($lexemId) {
  // We don't really use $cuv here
  $searchType = SEARCH_LEXEM_ID;
  SmartyWrap::assign('lexemId', $lexemId);
  if (!StringUtil::validateAlphabet($lexemId, '0123456789')) {
    $lexemId = '';
  }
  $lexem = Lexem::get_by_id($lexemId);
  $definitions = Definition::searchLexemId($lexemId, $exclude_unofficial);
  $searchResults = SearchResult::mapDefinitionArray($definitions);
  SmartyWrap::assign('results', $searchResults);
  if ($lexem) {
    $lexems = array($lexem);
    SmartyWrap::assign('cuv', $lexem->formNoAccent);
    if ($definitions) {
      SmartyWrap::assign('page_title', "Lexem: {$lexem->formNoAccent}");
    } else {
      SmartyWrap::assign('page_title', "Lexem neoficial: {$lexem->formNoAccent}");
      SmartyWrap::assign('exclude_unofficial', $exclude_unofficial);
    }
  } else {
    $lexems = array();
    SmartyWrap::assign('page_title', "Eroare");
    FlashMessage::add("Nu există niciun lexem cu ID-ul căutat.");
    header("HTTP/1.0 404 Not Found");
  }
  SmartyWrap::assign('lexems', $lexems);
}

SmartyWrap::assign('src_selected', $sourceId);

// Regular expressions
if ($hasRegexp) {
  $searchType = SEARCH_REGEXP;
  $numResults = Lexem::countRegexpMatches($cuv, $hasDiacritics, $sourceId, true);
  $lexems = Lexem::searchRegexp($cuv, $hasDiacritics, $sourceId, true);
  SmartyWrap::assign('numResults', $numResults);
  SmartyWrap::assign('lexems', $lexems);
  if (!$numResults) {
    FlashMessage::add("Niciun rezultat pentru {$cuv}.");
    header("HTTP/1.0 404 Not Found");
  }
}

// Definition.id search
if ($defId) {
  SmartyWrap::assign('defId', $defId);
  $searchType = SEARCH_DEF_ID;
  if (util_isModerator(PRIV_VIEW_HIDDEN)) {
      $def = Model::factory('Definition')->where('id', $defId)->where_in('status', array(ST_ACTIVE, ST_HIDDEN))->find_one();
  }
  else {
      $def = Model::factory('Definition')->where('id', $defId)->where('status', ST_ACTIVE)->find_one();
  }
  $definitions = array();
  if ($def) {
    $definitions[] = $def;
  } else {
    FlashMessage::add("Nu există nicio definiție cu ID-ul {$defId}.");
  }
  $searchResults = SearchResult::mapDefinitionArray($definitions);
  SmartyWrap::assign('results', $searchResults);
}

// Normal search
if ($searchType == SEARCH_INFLECTED) {
  $lexems = Lexem::searchInflectedForms($cuv, $hasDiacritics, true);
  if (count($lexems) == 0) {
    $cuv_old = StringUtil::tryOldOrthography($cuv);
    $lexems = Lexem::searchInflectedForms($cuv_old, $hasDiacritics, true);
  }
  if (count($lexems) == 0) {
    $searchType = SEARCH_MULTIWORD;
    $words = preg_split('/[ .-]+/', $cuv);
    if (count($words) > 1) {
      $ignoredWords = array_slice($words, 5);
      $words = array_slice($words, 0, 5);
      $definitions = Definition::searchMultipleWords($words, $hasDiacritics, $sourceId, $exclude_unofficial);
      SmartyWrap::assign('ignoredWords', $ignoredWords);
    }
  }
  if (count($lexems) == 0 && empty($definitions)) {
    $searchType = SEARCH_APPROXIMATE;
    $lexems = Lexem::searchApproximate($cuv, $hasDiacritics, true);
    if (count($lexems) == 1) {
      FlashMessage::add("Ați fost redirecționat automat la forma „{$lexems[0]->formNoAccent}”.");
    } else {
      if (!count($lexems)) {
        FlashMessage::add("Niciun rezultat relevant pentru „{$cuv}”.");
      }
      header("HTTP/1.0 404 Not Found");
    }
  }
  if (count($lexems) == 1 && $cuv != $lexems[0]->formNoAccent) {
    // Convenience redirect when there is only one correct form. We want all pages to be canonical
    $sourcePart = $source ? "-{$source->urlName}" : '';
    session_setVariable('redirect', true);
    session_setVariable('init_word', $cuv);
    util_redirect(util_getWwwRoot() . "definitie{$sourcePart}/{$lexems[0]->formNoAccent}" . ($xml ? '/xml' : ''));
  }

  SmartyWrap::assign('lexems', $lexems);
  if ($searchType == SEARCH_INFLECTED) {
    // For successful searches, load the definitions, inflections and linguistic articles
    $definitions = Definition::loadForLexems($lexems, $sourceId, $cuv, $exclude_unofficial);
    SmartyWrap::assign('wikiArticles', WikiArticle::loadForLexems($lexems));
  }

  if (isset($definitions)) {
    $totalDefinitionsCount = count($definitions);
    if(!$all && ($totalDefinitionsCount > PREVIEW_LIMIT)) {
      $definitions = array_slice($definitions, 0, PREVIEW_LIMIT);
      SmartyWrap::assign('totalDefinitionsCount', $totalDefinitionsCount);
    }
    $searchResults = SearchResult::mapDefinitionArray($definitions);
  }
}

$conjugations = NULL;
$declensions = NULL;
if ($searchType == SEARCH_INFLECTED || $searchType == SEARCH_LEXEM_ID || $searchType == SEARCH_FULL_TEXT || $searchType == SEARCH_MULTIWORD) {
  // Filter out hidden definitions
  $hiddenSources = array();
  SearchResult::filterHidden($searchResults, $hiddenSources);
  if (Config::get('global.aprilFoolsDay')) {
    foreach ($searchResults as $sr) {
      $sr->definition->htmlRep = StringUtil::iNoGrammer($sr->definition->htmlRep);
    }
  }

  SmartyWrap::assign('results', $searchResults);
  SmartyWrap::assign('hiddenSources', $hiddenSources);
 
  // Maps lexems to arrays of inflected forms (some lexems may lack inflections)
  // Also compute the text of the link to the paradigm div,
  // which can be 'conjugări', 'declinări' or both
  if (!empty($lexems)) {
    $conjugations = false;
    $declensions = false;
    foreach ($lexems as $l) {
      $lm = $l->getFirstLexemModel(); // One LexemModel suffices -- they all better have the same modelType.
      $isVerb = ($lm->modelType == 'V') || ($lm->modelType == 'VT');
      $conjugations |= $isVerb;
      $declensions |= !$isVerb;
    }
    $declensionText = $conjugations ? ($declensions ? 'conjugări / declinări' : 'conjugări') : 'declinări';

    if ($showParadigm) {
      $hasUnrecommendedForms = false;
      foreach ($lexems as $l) {
        foreach ($l->getLexemModels() as $lm) {
          $lm->getModelType();
          $lm->getSourceNames();
          $map = $lm->loadInflectedFormMap();
          $lm->addLocInfo();
          foreach ($map as $ifs) {
            foreach ($ifs as $if) {
              $hasUnrecommendedForms |= !$if->recommended;
            }
          }
        }
      }

      SmartyWrap::assign('hasUnrecommendedForms', $hasUnrecommendedForms);
    }
    SmartyWrap::assign('declensionText', $declensionText);
  }
}

// Collect source list to display in meta tags
$sourceList = array();
if (isset($searchResults)) {
  foreach ($searchResults as $row) {
      if (!in_array($row->source->shortName, $sourceList)) {
        $sourceList[] = $row->source->shortName;
      }
  }
}

// META tags - TODO move in a dedicated file
if ($cuv) {
  $page_keywords = "{$cuv}, definiție {$cuv}";
  $page_description = "Dicționar dexonline. Definiții";
  if (in_array('Sinonime', $sourceList)) {
    $page_keywords .= ", sinonime {$cuv}";
    $page_description .= ', sinonime';
  }
  if (in_array('Antonime', $sourceList)) {
    $page_keywords .= ", antonime {$cuv}";
    $page_description .= ', antonime';
  }
  if(!is_null($conjugations)) {
    $page_keywords .= ", conjugări {$cuv}";
    $page_description .= ', conjugări';
  }
  if (!is_null($declensions)) {
    $page_keywords .= ", declinări {$cuv}";
    $page_description .= ', declinări';
  }
  if (!is_null($conjugations) || !is_null($declensions)) {
    $page_keywords .= ", paradigmă {$cuv}";
    $page_description .= ', paradigme';
  }
  $page_keywords .= ", dexonline";
  $page_description .= " pentru {$cuv}";

  $page_title = '';
  if (count($sourceList)) {
    $page_description .= " din dicționarele: " . implode(", ", $sourceList);
    if (count($sourceList) == 1) {
        $page_title = ' ' . $sourceList[0];
    }
  }
  $page_title .= $showParadigm ? ' si paradigme' : '';

  SmartyWrap::assign('page_title', "{$cuv} - definitie{$page_title}");
  SmartyWrap::assign('page_keywords', $page_keywords);
  SmartyWrap::assign('page_description', $page_description);
}

// Ads
AdsModule::runAllModules(empty($lexems) ? null : $lexems, empty($definitions) ? null : $definitions);

SmartyWrap::assign('text', $text);
SmartyWrap::assign('searchType', $searchType);
SmartyWrap::assign('showParadigm', $showParadigm);
SmartyWrap::assign('locParadigm', session_user_prefers(Preferences::LOC_PARADIGM));
SmartyWrap::assign('paradigmLink', $paradigmLink);
SmartyWrap::assign('advancedSearch', $text || $sourceId);

/* Gallery */
$images = empty($lexems) ? array() : Visual::loadAllForLexems($lexems);
SmartyWrap::assign('images', $images);
if (count($images)) {
  SmartyWrap::addCss('gallery');
  SmartyWrap::addJs('gallery');
}

if (!$xml) {
  SmartyWrap::addCss('paradigm');
  SmartyWrap::display('search.tpl');

} else {
  header('Content-type: text/xml');
  SmartyWrap::displayWithoutSkin('searchXML.tpl');
}

if (Config::get('global.logSearch')) {
  $logDefinitions = isset($definitions) ? $definitions : array();
  $log = new Log($cuv, $redirectFrom, $searchType, $redirect, $logDefinitions);
  $log->logData();
}

?>
