<?php

// Addon-Assets (CSS/JS) einbinden, wenn wir im Backend sind und die Addon-Seite aktiv ist
if (rex::isBackend() && rex::getUser()) {
    if (rex_be_controller::getCurrentPagePart(1) == 'media_cats') {
        // CSS einbinden
        rex_view::addCssFile(rex_url::addonAssets('media_cats', 'media_cats.css'));
        
        // JavaScript einbinden - Tree Browser für categories Seite
        if (rex_be_controller::getCurrentPagePart(2) == 'categories') {
            rex_view::addJsFile(rex_url::addonAssets('media_cats', 'media_cats_tree.js'));
        } else {
            // Legacy JavaScript für andere Seiten
            rex_view::addJsFile(rex_url::addonAssets('media_cats', 'media_cats.js'));
        }
    }
}
