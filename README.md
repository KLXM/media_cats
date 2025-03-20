# Medienpool Kategorieverwaltung (media_cats)

Ein REDAXO AddOn für die sichere Verwaltung von Medienpool-Kategorien. Dies ist eine verbesserte Version basierend auf dem ursprünglichen "mediapool_categories" AddOn.

## Features

- Einzelne Bearbeitung von Kategorien zur Erhöhung der Datenintegrität
- Übersichtliche Akkordeon-Darstellung der Kategoriehierarchie
- Automatische Prüfung auf zyklische Abhängigkeiten
- Integrierte Backup-Funktion vor kritischen Änderungen
- Vollständig responsive Benutzeroberfläche

## Sicherheitsfeatures

Diese neue Version wurde speziell entwickelt, um Datenbank-Probleme zu vermeiden:

1. **Kategorien werden einzeln bearbeitet** - Dies verhindert komplexe Abhängigkeitsprobleme
2. **Automatische Zyklus-Erkennung** - Das System erkennt und verhindert fehlerhafte Hierarchien
3. **Backup-Management** - Erstellen und wiederherstellen von Backups mit einem Klick
4. **Korrekte Pfad-Aktualisierung** - Beim Verschieben werden alle untergeordneten Kategorien korrekt aktualisiert
5. **Validierung der Eingaben** - Alle Daten werden vor dem Speichern validiert

## Installation

1. Installieren Sie das AddOn über den REDAXO-Installer
2. Aktivieren Sie das AddOn
3. Navigieren Sie zu "Medienpool Kategorieverwaltung" im Hauptmenü

## Verwendung

### Kategorie bearbeiten

1. Klicken Sie auf die gewünschte Kategorie im Akkordeon, um sie zu öffnen
2. Ändern Sie den Namen oder die übergeordnete Kategorie
3. Klicken Sie auf "Speichern", um die Änderungen anzuwenden
4. Die Änderungen werden sofort in der Kategoriehierarchie sichtbar

### Hierarchien verwalten

- Die Einrückung im Akkordeon zeigt die aktuelle Hierarchieebene
- Im Dropdown "Übergeordnete Kategorie" werden alle verfügbaren Kategorien angezeigt
- Kategorien, die zu Zyklusproblemen führen würden, werden automatisch ausgeblendet
- Die Änderung einer übergeordneten Kategorie wirkt sich auf alle untergeordneten Kategorien aus

### Backups

- Erstellen Sie vor wichtigen Änderungen ein Backup der Kategoriestruktur
- Alle Backups werden mit Datum und Uhrzeit gespeichert
- Backups können wiederhergestellt oder gelöscht werden
- Bei Problemen können Sie jederzeit zu einem funktionierenden Zustand zurückkehren

## Systemvoraussetzungen

- REDAXO ab Version 5.18.1
- PHP 8.1 oder höher
- Media Manager AddOn muss installiert sein

## Hinweise für Entwickler

Das AddOn nutzt moderne PHP-Techniken und folgt Best Practices für REDAXO-AddOns:

- Strikte Typisierung mit PHP 8.1 Features
- Namespaces für bessere Code-Organisation
- Konsistente Fehlerbehandlung
- Bootstrap 3 kompatibles Frontend
- Responsives Design

## Autor

Thomas Skerbis  
[KLXM Crossmedia GmbH](https://klxm.de)
