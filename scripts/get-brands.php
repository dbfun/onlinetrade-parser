<?php

class BrandsGetter {

  private $db;
  public function __construct() {
    require('lib/application.php');
    PApplication::init(__DIR__.'/');

    require('inc/PageGetter.php');
    require('lib/extend/CDom/CDom.php');

    define('ABS_PATH', dirname(__FILE__));
    $this->db = PFactory::getDbo();
  }

  private $pageGetter;
  public function run() {

    $this->pageGetter = new PageGetter();

    $query = "SELECT `name`, `url` FROM `brands`";

    $items = $this->db->SelectSet($query);

    if(!is_array($items) || count($items) == 0) return;

    foreach($items as $item) {
      $start = microtime(true);
      $this->parse($item);
      $time = microtime(true) - $start;
      printf('Run:%.4F сек.'.PHP_EOL, $time);
    }

  }

  private function parse($item) {
    $uri = $this->getBrandUri($item['url']);
    $this->getPage($uri);
    if($this->pageHTTPCode != 200) {
      echo 'Not 200:'.$item['url'].PHP_EOL;
      return false;
    }

    $brand = $item;

    if(preg_match('~<img class="brand_page__tiop_part__img"\s*src="(.*?)"~s', $this->pageText, $m)) {
      $brand['picture'] = trim($m[1]);
    }

    if(preg_match('~<div class="brand_page__tiop_part__text">(.*?)<\/div>~s', $this->pageText, $m)) {
      $brand['description'] = trim($m[1]);
    }

    if(isset($brand['picture']) || isset($brand['description'])) {
      $this->saveItem($brand);
    }

  }

  private function saveItem($brand) {

    $set = array();

    foreach($brand as $field => $val) {
      if(is_array($val)) $val = serialize($val);
      $set[] = "`$field` = '".addslashes($val)."'";
    }

    $set = implode(',', $set);

    $query = "UPDATE `brands` SET $set WHERE `name` = '".addslashes($brand['name'])."'";
    // echo $query.PHP_EOL; die();
    $this->db->Query($query);

    return true;


  }

  private function getBrandUri($url) {
    return 'http://www.onlinetrade.ru/'.ltrim($url, '/');
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

}

$parser = new BrandsGetter();
$parser->run();