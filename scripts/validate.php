<?php

$dom = new DOMDocument;
$dom->Load('../data/test.xml');

$r = $dom->validate();

die(var_dump($r));