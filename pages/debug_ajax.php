<?php
// Debug-Datei für AJAX-Tests
// URL: /redaxo/index.php?page=media_cats/debug_ajax

use KLXM\MediaCats\CategoryManager;

echo '<h2>AJAX Debug für Media Categories</h2>';

// CategoryManager testen
try {
    $categoryManager = new CategoryManager();
    echo '<h3>✓ CategoryManager erfolgreich erstellt</h3>';
    
    // Test: getDirectChildren
    echo '<h4>Test: getDirectChildren(0)</h4>';
    $children = $categoryManager->getDirectChildren(0);
    echo '<pre>' . print_r($children, true) . '</pre>';
    
    // Test: Erste 10 Kategorien laden
    echo '<h4>Erste 10 Kategorien aus der Datenbank:</h4>';
    $sql = rex_sql::factory();
    $sql->setQuery('SELECT id, name, parent_id FROM ' . rex::getTable('media_category') . ' LIMIT 10');
    echo '<pre>';
    foreach ($sql as $row) {
        echo 'ID: ' . $row->getValue('id') . ' | Name: ' . $row->getValue('name') . ' | Parent: ' . $row->getValue('parent_id') . "\n";
    }
    echo '</pre>';
    
    // Test: AJAX-URL generieren
    echo '<h4>AJAX-URL:</h4>';
    $ajaxUrl = rex_url::backendPage('media_cats/ajax');
    echo '<code>' . $ajaxUrl . '</code>';
    
    // Test: CSRF-Token
    echo '<h4>CSRF-Token:</h4>';
    $csrfToken = rex_csrf_token::factory('media_cats');
    echo '<code>' . $csrfToken->getValue() . '</code>';
    
} catch (Exception $e) {
    echo '<h3 style="color: red;">❌ Fehler: ' . $e->getMessage() . '</h3>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}

// Direkte AJAX-Simulation
echo '<h3>Direkte AJAX-Simulation</h3>';
echo '<div>';
echo '<button onclick="testAjax()">AJAX-Request testen</button>';
echo '<div id="ajax-result"></div>';
echo '</div>';

echo '<script>
function testAjax() {
    var resultDiv = document.getElementById("ajax-result");
    resultDiv.innerHTML = "<p>Lade...</p>";
    
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "' . rex_url::backendPage('media_cats/ajax') . '", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            resultDiv.innerHTML = "<h4>Status: " + xhr.status + "</h4>";
            resultDiv.innerHTML += "<h4>Response Headers:</h4><pre>" + xhr.getAllResponseHeaders() + "</pre>";
            resultDiv.innerHTML += "<h4>Response Body:</h4><pre>" + xhr.responseText.substring(0, 500) + "</pre>";
        }
    };
    
    xhr.send("action=load_children&parent_id=0");
}
</script>';
?>
