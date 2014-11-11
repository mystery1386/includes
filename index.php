<?php
require_once "lib/core/io/format/FormatFactory.php";
require_once "lib/core/globals/Envirement.php";

$test = FormatFactory::load("test/test.yml");
print_r($test);