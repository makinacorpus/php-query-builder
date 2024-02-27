<?php

use MakinaCorpus\QueryBuilder\Schema\Diff\Change\Template\Generator;

require_once \dirname(__DIR__) . '/vendor/autoload.php';

$generator = new Generator();
$generator->generateFromFile();
