<?php

require __DIR__ . '/../vendor/autoload.php';

use Last1971\ChipDipParser;

$test = new ChipDipParser\ChipDipParser();
//var_dump($test->searchByCode('45325'));
var_dump($test->searchByName('idc-10f'));