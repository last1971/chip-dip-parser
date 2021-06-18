<?php

require __DIR__ . '/../vendor/autoload.php';

use Last1971\ChipDipParser;

$test = new ChipDipParser\ChipDipParser();
//var_dump($test->searchByCode('9000074770'));
var_dump($test->searchByName('ascasfasdcfasdfc'));