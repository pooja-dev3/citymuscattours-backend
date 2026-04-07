<?php
$dir = 'c:/wamp64/www/frontend/php-frontend/php-backend/PhpPlugin/iPAYPlugin/extract_jar/com/fss/plugin';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'class') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'File Not found') !== false || strpos($content, 'validate the path') !== false) {
            echo "Found in: " . $file->getFilename() . "\n";
        }
    }
}
echo "Done.\n";
