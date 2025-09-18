<?php

// Test rex_media_category_select

require_once '/Users/thomas/redaxo_instances/core/project/public/redaxo/src/core/boot.php';

try {
    $categorySelect = new rex_media_category_select(false);
    $categorySelect->setName('test_select');
    
    $selectHtml = $categorySelect->get();
    echo "HTML Output:\n";
    echo $selectHtml;
    echo "\n\n";
    
    // Parse options
    if (preg_match_all('/<option[^>]*value="(\d+)"[^>]*>(.*?)<\/option>/i', $selectHtml, $matches, PREG_SET_ORDER)) {
        echo "Found " . count($matches) . " options:\n";
        foreach ($matches as $match) {
            echo "Value: " . $match[1] . " - Text: " . trim(strip_tags($match[2])) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
