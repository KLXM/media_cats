<?php

// Aktuellen Seitenparameter ermitteln
$subpage = rex_be_controller::getCurrentPagePart(2);

// Titel anzeigen
echo rex_view::title(rex_i18n::msg('media_cats_title'));

// Unterseite einbinden
rex_be_controller::includeCurrentPageSubPath();
