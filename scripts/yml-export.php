<?php

class YmlExporter {

  private $db;
  public function __construct() {
    require('lib/application.php');
    PApplication::init(__DIR__.'/');
    require('inc/yml-extend.php');
    $this->db = PFactory::getDbo();
  }

  private $yml, $rootParentSku = '1';
  public function run() {
    $parms = array(
      'name' => 'Magazin',
      'company' => 'Magazin',
      'url' => 'http://www.magazin.ru/'
    );


    $fileNameBase = '../data/export-';
    for ($i = 0; $i < 15; $i++) {
      $this->yml = new yml($parms);
      $this->categories();
      $limit = $i*3000;
      $this->offers($limit);

      $fileName = $fileNameBase . $i . '.xml';
      file_put_contents($fileName, $this->yml->get());
    }

  }

  private function categories() {
    $query = "SELECT c1.*, c2.old_sku AS `root_parent_sku`
              FROM `categories` AS `c1`
              LEFT JOIN `categories` AS `c2` ON c1.parent_sku = c2.old_sku";

    $cats = $this->db->SelectSet($query);
    if(!is_array($cats) || count($cats) == 0) return false;
    foreach($cats as $cat) {
      $this->yml->addCat($cat['name'], $cat['old_sku'], $cat['root_parent_sku'] == 0 ? -1 : $cat['parent_sku']);
    }
  }

  private function offers($limit) {
    $query = "SELECT * FROM `items` LIMIT ".$limit.', 3000';
    $items = $this->db->SelectSet($query);
    if(!is_array($items) || count($items) == 0) return false;
    foreach($items as $item) {

      $params = (array)unserialize($item['options']);
      $pictures = unserialize($item['pictures']);
      if(is_array($pictures) && count($pictures) > 0) foreach($pictures as &$pic){
        $pic = 'http://www.onlinetrade.ru/'.ltrim($pic, '/');
      }

      $description = $item['description'];

      if($item['complectation']) $description .= '<p>Комплектация:</p>' . $item['complectation'];

      if($item['brand_sku']) {
        $description .= '<p>Производитель: ' . $item['brand_sku'] . '</p>';
        $params['Производитель'] = $item['brand_sku'];
      }

      if($item['brand_country']) $description .= '<p>Страна производителя: ' . $item['brand_country'] . '</p>';


      if($item['guaranty']) {
        $description .= '<p>Гарантия: ' . $item['guaranty'] . '</p>';
        $params['Гарантия'] = $item['guaranty'];
      }

      $weight = null;
      if(isset($params['Вес'])) {
        $weight = $params['Вес'];
        // unset($params['Вес']);
      }

      $this->yml->addOffer(array(
        'id' => $item['old_sku'],
        'url' => $item['old_sku'],
        'price' => $item['price'],
        'categoryId' => $item['cat_sku'],
        'picture' => $pictures,
        'name' => $item['name'],
        'description' => $description,
        'vendor' => $item['brand_sku'],
        //'vendorCode' => 'ot-'.$item['brand_code'],
        'vendorCode' => 'ot-'.sprintf('%1$05d', $item['new_sku']),
        'country_of_origin' => $item['brand_country'],
        'manufacturer_warranty' => true,
        'store' => true,
        'delivery' => true,
        'weight' => $weight,
        'params' => $params
      ));
    }

  }

}

$ymlExporter = new YmlExporter();
$ymlExporter->run();