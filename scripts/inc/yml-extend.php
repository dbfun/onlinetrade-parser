<?php

class Yml extends XmlWriter {

  public function __construct(array $parms) {
    $this->openMemory();
    $this->setIndent(true);
    $this->setIndentString('  ');
    $this->startDocument('1.0', 'utf-8');

    $this->startDTD('yml_catalog', '','http://partner.market.yandex.ru/pages/help/shops.dtd');
    $this->endDTD();

    $this->startElement('yml_catalog');
    $this->writeAttribute('date', date('Y-m-d H:i', time()));

      $this->startElement('shop');

        $this->writeElement('name', $parms['name']);
        $this->writeElement('company', $parms['company']);
        $this->writeElement('url', $parms['url']);

        $this->startElement('currencies');
          $this->startElement('currency');
            $this->writeAttribute('id', 'RUR');
            $this->writeAttribute('rate', '1');
            $this->writeAttribute('plus', '0');
          $this->endElement();
        $this->endElement();
  }

  private $cats = array();
  public function addCat($name, $id, $parentId = -1) {
    $cat = array('n' => $this->prepare($name), 'id' => $id);
    if($parentId != -1) $cat['pid'] = $parentId;
    $this->cats[] = $cat;
  }

  public function addOffer($offer) {
    $this->offers[] = $offer;
  }

  public function get() {
      $this->_addCats();
      $this->_addOffers();
      $this->endElement();
    $this->endElement();

    return $this->outputMemory(true);
  }

  private function _addCats() {
    $this->startElement('categories');
      foreach($this->cats as $cat) {
        $this->startElement('category');
          $this->writeAttribute('id', $cat['id']);
          if(isset($cat['pid'])) $this->writeAttribute('parentId', $cat['pid']);
          $this->text($cat['n']);
        $this->endElement();
      }
    $this->endElement();
  }

  private function _addOffers() {
    $this->startElement('offers');
      foreach($this->offers as $offer) {
        $this->startElement('offer');
          $this->writeAttribute('id', $offer['id']);
          $this->writeAttribute('type', 'vendor.model');
          $this->writeAttribute('available', 'true');

          $this->writeElement('url', $offer['url']);
          $this->writeElement('price', number_format($offer['price'], 0, '.', ''));
          $this->writeElement('currencyId', 'RUR');
          $this->writeElement('categoryId', $offer['categoryId']);

          $pictures = array_filter((array)$offer['picture']);

          if(count($pictures) > 0) foreach($pictures as $pic) {
            $this->writeElement('picture', $pic);
          }

          if(isset($offer['delivery'])) $this->writeElement('delivery', $offer['delivery'] ? 'true' : 'false');
          $this->writeElement('name', $this->prepare($offer['name']));

          if(isset($offer['description'])) {
            $this->startElement('description');
              $this->writeCData($offer['description']);
            $this->endElement();
          }

          if(isset($offer['vendor']) && $offer['vendor']) $this->writeElement('vendor', $this->prepare($offer['vendor']));
          if(isset($offer['vendorCode']) && $offer['vendorCode']) $this->writeElement('vendorCode', $this->prepare($offer['vendorCode']));
          if(isset($offer['manufacturer_warranty'])) $this->writeElement('manufacturer_warranty', $offer['manufacturer_warranty'] ? 'true' : 'false');
          if(isset($offer['country_of_origin'])) $this->writeElement('country_of_origin', $this->prepare($offer['country_of_origin']));
          if(isset($offer['store'])) $this->writeElement('store', $offer['store'] ? 'true' : 'false');
          if(isset($offer['delivery'])) $this->writeElement('delivery', $offer['delivery'] ? 'true' : 'false');

          if(isset($offer['weight']) && $offer['weight']) {
            $weight = $this->getWeight($offer['weight']);
            if($weight) $this->writeElement('weight', number_format($weight, 3, '.', ''));
          }

          $params = array_filter((array)$offer['params']);
          if(count($params) > 0) foreach($params as $pName => $pVal) {
            $this->startElement('param');
              $this->writeAttribute('name', $this->prepare($pName));
              $this->writeCData($pVal);
            $this->endElement();

          }

        $this->endElement();
      }
    $this->endElement();
  }

  private function prepare($text) {
    $text = html_entity_decode($text, null, 'UTF-8');
    $text = strip_tags($text);
    $text = preg_replace('/\s+/', ' ', trim($text));
    $text = htmlspecialchars($text, null, 'UTF-8');
    return $text;
  }

  private function getWeight($weight) {
    $weight = preg_replace('~,~', '.', trim($weight));
    switch(true) {
      case preg_match('~([0-9]*\.?[0-9]*)\s*кг$~i', $weight, $m):
        return $m[1];
        break;
      case preg_match('~([0-9]*\.?[0-9]*)\s*г$~i', $weight, $m):
        $w = $m[1];
        if($w < 1) $w = $w * 1000;
        return $w/1000;
        break;
      default:
        return false;
    }

  }




}