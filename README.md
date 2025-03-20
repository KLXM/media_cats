# Medienpool Kategorieverwaltung (media_cats)

Mit diesem AddOn können Medienpool-Kategorien umbenannt und umsortiert werden.

## Features

- Sicheres Verwalten der Medienkategorien
- Verhinderung zyklischer Abhängigkeiten
- Automatische Backups vor Änderungen
- Backup-Wiederherstellung im Fehlerfall
- Intuitive Benutzeroberfläche

## Sicherheitshinweis

Die Verwaltung von Medienpool-Kategorien kann die Struktur der REDAXO-Website beeinflussen. Es wird dringend empfohlen, vor der Nutzung des AddOns ein vollständiges Backup der Website zu erstellen.

Das AddOn erstellt automatisch Sicherungskopien der Kategorie-Tabelle, bevor Änderungen vorgenommen werden. Diese Backups können im Notfall wiederhergestellt werden.

## Verwendung

1. Installieren Sie das AddOn über den REDAXO-Installer.
2. Navigieren Sie zu "Medienpool Kategorieverwaltung" im Hauptmenü.
3. Bei der ersten Verwendung wird eine Sicherheitsabfrage angezeigt. Bestätigen Sie diese, um fortzufahren.
4. Ein Backup wird automatisch erstellt.
5. Nun können Sie die Kategorienamen ändern und die Hierarchie durch Anpassen der übergeordneten Kategorie modifizieren.
6. Klicken Sie auf "Speichern", um die Änderungen zu übernehmen.

## Backups

Backups werden automatisch bei der ersten Verwendung und auf Anfrage erstellt. In der Registerkarte "Backups" können Sie:

- Manuelle Backups erstellen
- Bestehende Backups wiederherstellen
- Nicht mehr benötigte Backups löschen

## Technische Details

Das AddOn verwendet moderne PHP 8.1+ Features und einen sicheren Ansatz zur Datenbankmanipulation:

- SQL-Transaktionen für sichere Aktualisierungen
- Erkennung und Verhinderung zyklischer Abhängigkeiten
- Vollständige Aktualisierung aller abhängigen Pfade
- Validierung aller Eingaben

## Fehlerbehandlung

Sollten Fehler auftreten, können Sie:

1. Auf die Registerkarte "Backups" wechseln
2. Ein vorheriges Backup wiederherstellen
3. Den Cache im System löschen

## Autor

Thomas Skerbis
