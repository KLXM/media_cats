# 🐈‍⬛🐈 KLXM MediaCats

Endlich Medienkategorien verschieben in REDAXO

**Das Problem kennt jeder:** Im Standard-REDAXO kannst du Medienkategorien umbenennen, aber nicht verschieben oder neu anordnen. Wir alle haben es schon erlebt: Die Struktur passt nicht mehr, aber es gibt keine Möglichkeit, die Hierarchie zu ändern, ohne die DB direkt zu bearbeiten. 😫

**media_cats** füllt diese Lücke und ermöglicht endlich das Verschieben von Medienkategorien - und zwar sicher!

## Was kann media_cats?

- **Kategorien verschieben**: Ordne deine Medienkategorien neu an, indem du übergeordnete Kategorien änderst
- **Kategorienamen ändern**: Klar, das ging schon vorher - aber jetzt mit Backup!
- **Sicher dank Einzelbearbeitung**: Änderungen nur an einer Kategorie gleichzeitig - verhindert Chaos
- **Automatische Zyklus-Erkennung**: Keine kaputten Strukturen durch zirkuläre Abhängigkeiten
- **Integriertes Backup-System**: Automatische Backups vor jeder Änderung und Wiederherstellungsmöglichkeit

## Warum ist das wichtig?

Vielleicht denkst du: "Die Medienkategorien einfach neu anzulegen wäre doch einfacher!" Aber alle, die schon mal hunderte Medien neu kategorisieren mussten, wissen: Das ist ein Alptraum. Mit media_cats kannst du die Struktur anpassen, ohne Medien verschieben zu müssen.

## Benutzung

1. "Medienpool Kategorieverwaltung" im Hauptmenü öffnen
2. Klicke auf eine Kategorie im Akkordeon, um sie zu bearbeiten
3. Wähle eine neue übergeordnete Kategorie aus dem Dropdown
4. Speichern und fertig!

## Für Nerds: Die Technik dahinter

- Sichere Verarbeitung der Pfade und Abhängigkeiten
- Automatische Aktualisierung aller Unterkategorien beim Verschieben
- Komplette Validierung aller Änderungen vor der Durchführung
- Kompatibel mit PHP 8.1+ und REDAXO 5.18.1+
- Sauberes Namespacing mit \KLXM\MediaCats


## Credits

Entwickelt von Thomas Skerbis

*Es geht nicht darum, Probleme zu haben - es geht darum, Lösungen zu finden. Willkommen bei REDAXO!*
