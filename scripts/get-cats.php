<?php

class CategoriesGetter {

  private $db;
  public function __construct() {
    require('lib/application.php');
    PApplication::init(__DIR__.'/');

    require('inc/PageGetter.php'); // todo
    require('lib/extend/CDom/CDom.php');

    define('ABS_PATH', dirname(__FILE__));
    $this->db = PFactory::getDbo();
  }

  private $minCatId = 1, $maxCatId = 2100, $curCatId = 0, $pageGetter;

  public function run() {

    $this->pageGetter = new PageGetter();

    $this->parseRootCat('http://www.onlinetrade.ru/catalogue/?ajax_request=1&required_tpl=0');

    for($this->curCatId = $this->minCatId; $this->curCatId <= $this->maxCatId; $this->curCatId++) {

      $page = 0;
      while($this->parseCat($page)) {
        $page++;
      }

    }

  }

  private $excludeCats = array(375);
  private function parseRootCat($uri) {
    $this->getPage($uri);
    if($this->pageHTTPCode != 200) return false;

    $dom = CDom::fromString($this->pageText);
    $divs = $dom->find('.gim_parent');

    if(count($divs) == 0) return false;

    foreach ($divs as $div) {
      $sectionHtml = $div->outerHtml();

      $sectionItem = array();
      if(preg_match('~<div class="gim_title".*?>\s*<a href="(.*?)".*?>(.*?)<\/a>~s', $sectionHtml, $m)) { // section name OK
        $sectionItem['uri'] = $this->trimHash($m[1]);
        $sectionItem['name'] = $m[2];
        if(preg_match('~-c([0-9]+)\/$~', $sectionItem['uri'], $m2)) { // section id OK
          $sectionItem['old_sku'] = $m2[1];
          if(preg_match('~<img src="(.*?)"~s', $sectionHtml, $m3)) { // section image OK
            $sectionItem['picture'] = $m3[1];
          }
          $this->saveSection($sectionItem);
        }
      }
    }

  }


  private function parseCat($page) {
    $uri = $this->getCatUri($this->curCatId, $page);
    $this->getPage($uri);

    if($this->pageHTTPCode != 200) return false;

    if($this->hasPageItems()) {
      $this->parseItems();
      return true;
    } else {
      $this->parseSubCats();
      return false;
    }
  }

  private $maxRetries = 3, $pageText, $pageHTTPCode;
  private function getPage($uri) {
    for($i = 1; $i <= $this->maxRetries; $i++) {
      $this->pageText = iconv('cp1251', 'utf-8', $this->pageGetter->get($uri)->content);
      $this->pageHTTPCode = $this->pageGetter->HTTPCode;
      if($this->pageHTTPCode != 503) break;
      echo 'Retry '.$uri.' ...'.PHP_EOL;
      sleep(5);
    }
  }


  private function parseSubCats() {
    $dom = CDom::fromString($this->pageText);
    $divs = $dom->find('.gscm_parent');
    if(count($divs) == 0) return false;

    foreach ($divs as $div) {
      $sectionHtml = $div->outerHtml();

      $sectionItem = array();
      if(preg_match('~<div class="gscm_title">\s*<a href="(.*?)".*?>(.*?)<\/a>~s', $sectionHtml, $m)) { // section name OK

        $sectionItem['parent_sku'] = $this->curCatId;
        $sectionItem['uri'] = $this->trimHash($m[1]);
        $sectionItem['name'] = $m[2];

        if(preg_match('~-c([0-9]+)\/$~', $sectionItem['uri'], $m2)) { // section id OK
          $sectionItem['old_sku'] = $m2[1];

          $this->saveSection($sectionItem);

          $domCats = CDom::fromString($sectionHtml);
          $divsCats = $domCats->find('.gscm_children .gscm_children__into_child');

          if(count($divsCats) > 0) { // подкатегории
            foreach ($divsCats as $divCat) {
              $sectionSubItem = array();
              $sectionSubItem['parent_sku'] = $sectionItem['old_sku'];
              $subSectionHtml = $divCat->outerHtml();
              if(preg_match('~<p class="gscm_children__into_child__text.*?">\s*<a href="(.*?)".*?>(.*?)<\/a>.*?<\/p>~s', $subSectionHtml, $m3)) {
                $sectionSubItem['uri'] = $this->trimHash($m3[1]);
                $sectionSubItem['name'] = $m3[2];
                if(preg_match('~-c([0-9]+)\/$~', $sectionSubItem['uri'], $m4)) { // section id OK
                  $sectionSubItem['old_sku'] = $m4[1];
                  if(preg_match('~<img src="(.*?)"~s', $subSectionHtml, $m5)) { // section image OK
                    $sectionSubItem['picture'] = $m5[1];
                  }

                  $this->saveSection($sectionSubItem);
                }
              }
            }
          }
        }
      }
    }
    return true;
  }

  private function trimHash($uri) {
    return preg_replace('~(.*?)#.*$~', '\\1', trim($uri));
  }

  private function parseItems() { // http://www.onlinetrade.ru/catalogue/?cat_id=92&ajax_request=1&per_page=50&page=0
    $dom = CDom::fromString($this->pageText);
    $divs = $dom->find('.category_card__codes_area');

    if(count($divs) == 0) return false;

    foreach ($divs as $div) {
      $itemHtml = $div->outerHtml();

      $item = array();
      if(preg_match('~<span><strong>Код товара:<\/strong>\s*([0-9]*)<\/span>~s', $itemHtml, $m)) { // section name OK
        $item['cat_sku'] = $this->curCatId;
        $item['old_sku'] = $m[1];
        $this->saveItem($item);
      }
    }

  }

  private function saveItem($item) {
    $query = "SELECT `id` FROM `items` WHERE `old_sku` = '".addslashes($item['old_sku'])."'";
    $oldItemId = $this->db->SelectValue($query);
    if($oldItemId > 0) return; // only one relation
    $query = "INSERT INTO `items` (`distributor_id`, `old_sku`, `cat_sku`, `created`) VALUES(
      1, '".addslashes($item['old_sku'])."', '".addslashes($item['cat_sku'])."', NOW())";
    // echo $query.PHP_EOL; die();
    $this->db->Query($query);
  }

  private function saveSection($section) {
    if(!isset($section['old_sku']) || !$section['old_sku']) {
      echo "No section id ".var_dump($section).PHP_EOL;
    }
    $query = "SELECT `id` FROM `categories` WHERE `old_sku` = '".addslashes($section['old_sku'])."'";
    $oldSection = $this->db->SelectValue($query);
    if($oldSection > 0) {
      $section['id'] = $oldSection;
      $this->updateSection($section);
    } else {
      $this->insertSection($section);
    }

  }

  private function insertSection($section) {
    $section['distributor_id'] = 1;

    $fields = array_keys($section);
    $fields = implode(',', $this->wrap($fields, '`', '`'));

    $values = array_values($section);
    $values = $this->escape($values);
    $values = implode(',', $this->wrap($values, '\'', '\''));

    $query = "INSERT INTO `categories` ($fields, `created`) VALUES ($values, NOW());";
    // echo $query.PHP_EOL; die();
    $this->db->Query($query);
  }

  private function updateSection($section) {
    $set = array();

    foreach($section as $field => $val) {
      $set[] = "`$field` = '".addslashes($val)."'";
    }

    $set = implode(',', $set);

    $query = "UPDATE `categories` SET $set, `updated` = NOW() WHERE `id` = ".(int)$section['id'];
    // echo $query.PHP_EOL; die();
    $this->db->Query($query);
  }



  private function getCatUri($catId, $page) {
    return 'http://www.onlinetrade.ru/catalogue/?cat_id='.(int)$catId.'&ajax_request=1&per_page=50&page='.(int)$page;
  }

  // http://www.onlinetrade.ru/catalogue/?cat_id=1&ajax_request=1&per_page=50&page=0 - подразделы
  // http://www.onlinetrade.ru/catalogue/?cat_id=1&ajax_request=92&per_page=50&page=0 - товары

  private function hasPageItems() {
    return !preg_match('~<div class="mainContent"~s', $this->pageText);
  }

  private function wrap($fields, $left, $right) {
    foreach($fields as &$f) {
      $f = $left.$f.$right;
    }
    return $fields;
  }

  private function escape($fields) {
    foreach($fields as &$f) {
      $f = addslashes($f);
    }
    return $fields;
  }

}

$parser = new CategoriesGetter();
$parser->run();