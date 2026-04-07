<?php
$file = 'c:\wamp64\www\frontend\php-frontend\php-backend\PhpPlugin\JavaBridge\java\Java.inc';
$content = file_get_contents($file);

preg_match_all('/(?:class|interface)\s+([a-zA-Z0-9_]+)/i', $content, $matches);
$classes = array_unique($matches[1]);
$replacements = 0;

foreach ($classes as $class) {
    $pattern = '/function\s+' . preg_quote($class, '/') . '\s*\(/i';
    $replacement = 'function __construct(';
    $content = preg_replace($pattern, $replacement, $content, -1, $count);
    $replacements += $count;
}

if ($replacements > 0) {
    file_put_contents($file, $content);
    echo "Fixed $replacements constructors in Java.inc.\n";
}
else {
    echo "No constructors to fix.\n";
}
