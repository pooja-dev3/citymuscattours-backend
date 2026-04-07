<?php
$file = 'c:/wamp64/www/frontend/php-frontend/php-backend/PhpPlugin/iPAYPlugin/extract_jar/com/fss/plugin/ParseResouce.class';
$content = file_get_contents($file);
preg_match_all('/[\x20-\x7E]{5,}/', $content, $matches);
foreach ($matches[0] as $match) {
    echo $match . "\n";
}
