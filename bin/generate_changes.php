<?php

use MakinaCorpus\QueryBuilder\Schema\Diff\Generator\Generator;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

$generator = new Generator();
$generator->generateFromFile();
