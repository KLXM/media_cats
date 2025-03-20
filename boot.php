<?php

// Autoloader für den Namespace KLXM\MediaCats
rex_autoload::addDirectory(rex_path::addon('media_cats', 'lib'));

// Addon-Assets (CSS/JS) einbinden, wenn wir im Backend sind und die Addon-Seite aktiv ist
if (rex::isBackend() && rex::getUser()) {
    if (rex_be_controller::getCurrentPagePart(1) == 'media_cats') {
        // CSS einbinden
        rex_view::addCssFile(rex_url::addonAssets('media_cats', 'media_cats.css'));
        
        // JavaScript einbinden
        rex_view::addJsFile(rex_url::addonAssets('media_cats', 'media_cats.js'));
    }
}
