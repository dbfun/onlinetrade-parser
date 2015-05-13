<?php

class PageGetter {

  private $ch;
  public function __construct($login = '', $password = '') {
    $this->login = $login;
    $this->password = $password;
    $this->ch = curl_init();
    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
    if($login && $password) curl_setopt($this->ch, CURLOPT_USERPWD, $login.':'.$password);
  }

  public function __destruct() {
    curl_close($this->ch);
  }

  public function __clone() {
    self::__construct($this->login, $this->password);
  }

  public function __get($name) {
    return isset($this->{$name}) ? $this->{$name} : null;
  }

  private $uri, $content, $header, $err, $errmsg, $HTTPCode;
  public function get($uri) {
    $this->uri = $uri;
    curl_setopt($this->ch, CURLOPT_URL, $this->uri);
    $this->content = trim(curl_exec($this->ch));
    $this->header  = curl_getinfo($this->ch);
    $this->err     = curl_errno($this->ch);
    $this->errmsg  = curl_error($this->ch);
    $this->HTTPCode = $this->header['http_code'];
    return $this;
  }

  public function curl_setopt($opt, $val) {
    curl_setopt($this->ch, $opt, $val);
  }

  public function upload($sourcePicUri, $destPicName, $isOverWrite = false) {

    if (!$isOverWrite && file_exists($destPicName)) return $destPicName;

    $uploader = clone $this;

    $fh = fopen($destPicName, 'w');
    $uploader->curl_setopt(CURLOPT_FILE, $fh);
    $uploader->get($sourcePicUri);
    fclose($fh);

    $isPicture = (200 == $uploader->header['http_code']) && preg_match("~image\/.*~i", $uploader->header['content_type']);

    if ($isPicture) {
      if (file_exists($destPicName) && filesize($destPicName) > 0) {
        return $destPicName;
      }
      unlink($destPicName);
      return null;
    }
    unlink($destPicName);
    return null;
  }

}