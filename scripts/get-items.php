<?php

class ItemsGetter {

  private $db;
  public function __construct() {
    die('one time');
    require('lib/application.php');
    PApplication::init(__DIR__.'/');

    require('inc/PageGetter.php');
    require('lib/extend/CDom/CDom.php');

    define('ABS_PATH', dirname(__FILE__));
    $this->db = PFactory::getDbo();
  }

  private $pageGetter;
  private $excludeCats = array(375,366,367,516,557,556,1240,435,1582,1600,1916,1919);
  public function run() {

    $this->pageGetter = new PageGetter();

    $query = "SELECT `id`, `old_sku` FROM `items`
      WHERE `cat_sku` NOT IN (".implode(',', $this->excludeCats).") AND `updated` = '0000-00-00 00:00:00' ORDER BY RAND()";

    // $query .= " ORDER BY RAND() LIMIT 10"; // todo TEST
    // $query = "SELECT `id`, `old_sku` FROM `items` WHERE `old_sku` = '198594'"; // todo test

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
    $uri = $this->getItemUri($item['old_sku']);
    $this->getPage($uri);
    if($this->pageHTTPCode != 200) {
      echo 'Not 200:'.$item['old_sku'].PHP_EOL;
      return false;
    }

    $shopItem = $item;

    if(preg_match('~<h1.*?>(.*?)<\/h1>~', $this->pageText, $m)) {
      $shopItem['name'] = trim($m[1]);
    }

    if(preg_match('~itemprop="brand">(.*?)<\/~s', $this->pageText, $m)) {
      $shopItem['brand_sku'] = trim($m[1]);
    } else if(preg_match('~class="item_brand">.*?<a href="\/brands\/.*?".*?>Подробнее о (.*?)</a>~s', $this->pageText, $m)) {
      $shopItem['brand_sku'] = trim($m[1]);
    }

    if(preg_match('~код производителя:\s*(.*?)\s*&nbsp;~', $this->pageText, $m)) {
      $shopItem['brand_code'] = trim($m[1]);
    }

    if(preg_match('~страна-производитель:\s*(.*?)\s*<~s', $this->pageText, $m)) {
      $shopItem['brand_country'] = trim($m[1]);
    }

    if(preg_match('~itemprop="description">(.*?)<\/~s', $this->pageText, $m)) {
      $shopItem['meta_description'] = trim($m[1]);
    }

    $dom = CDom::fromString($this->pageText);
    $div = $dom->find('#description');
    $shopItem['description'] = trim($div->outerHtml());

    if(preg_match('~<p class="associate_tab__tab__title">Комплектация</p>\s*<div class="table">\s*(<ul>.*?<\/ul>.*?)<\/div>~s', $this->pageText, $m)) {
      $shopItem['complectation'] = trim($m[1]);
    }

    $divs = $dom->find('.table.hars table tr');
    if(count($divs) > 0) {
      foreach($divs as $div) {
        $str = $div->outerHtml();
        if(preg_match('~<td class="hars_left".*?>(.*?)<\/td>.*<td class="hars_right">(.*?)<\/td>~s', $str, $m)) {
          $shopItem['options'][trim($m[1])] = trim($m[2]);
        }
      }
    }

    if(preg_match('~<strong>официальная гарантия<\/strong>\s*(.*?:.*?)\s*\(~s', $this->pageText, $m)) {
      $shopItem['guaranty'] = trim($m[1]);
    }


    if(preg_match('~itemprop="price">(.*?)<\/~s', $this->pageText, $m)) {
      $shopItem['price'] = trim($m[1]);
    }

    if(preg_match_all('~<a class="lightbox".*?rel="(.*?)"~s', $this->pageText, $m)) {
      if(isset($m[1])) {
        foreach($m[1] as $str) {
          if(preg_match('~\|(\/img\/items\/b\/.*)\|~s', $str, $m)) {
            $shopItem['pictures'][] = trim($m[1]);
          }
        }
        $shopItem['pictures'] = array_values(array_unique($shopItem['pictures']));
      }
    }

    if(preg_match('~href="#tab16"\s*rel="(.*?)"~s',$this->pageText, $m)) {
      $shopItem['url'] = $this->trimHash($m[1]);
    }



    $this->saveItem($shopItem);
  }

  private function saveItem($shopItem) {

    $isValid = isset($shopItem['name'], $shopItem['price']);
    if(!$isValid) {
      echo 'Is not valid:'.$shopItem['old_sku'].PHP_EOL;
      return false;
    }

    // echo var_dump($shopItem);
    // echo "{$isValid}\t{$score}\t{$shopItem['old_sku']}".PHP_EOL;
    // die(var_dump($shopItem));

    $set = array();

    foreach($shopItem as $field => $val) {
      if(is_array($val)) $val = serialize($val);
      $set[] = "`$field` = '".addslashes($val)."'";
    }

    $set = implode(',', $set);

    $query = "UPDATE `items` SET $set, `updated` = NOW() WHERE `id` = ".(int)$shopItem['id'];
    // echo $query.PHP_EOL; die();
    $this->db->Query($query);

    return true;


  }

  private function getScore($shopItem) {
    $score = 0;
    $score += isset($shopItem['name']);

    $score += isset($shopItem['brand_sku']);
    $score += isset($shopItem['brand_code']);
    $score += isset($shopItem['brand_country']);

    $score += isset($shopItem['meta_description']);
    $score += isset($shopItem['description']);
    $score += isset($shopItem['complectation']);
    $score += isset($shopItem['options']);

    $score += isset($shopItem['guaranty']);
    $score += isset($shopItem['price']);
    $score += isset($shopItem['pictures']);
    $score += isset($shopItem['url']);
    return $score;
  }

  private function getItemUri($sku) {
    return 'http://www.onlinetrade.ru/goods.php?ajax_request=1&id='.$sku.'&required_tpl=0';
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

  private function trimHash($uri) {
    return preg_replace('~(.*?)#.*$~', '\\1', trim($uri));
  }


}

$parser = new ItemsGetter();
$parser->run();