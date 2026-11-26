# Changelog
Diese Datei listet die Änderungen jeder Version auf, auf Deutsch (diese Datei) und Englisch (`CHANGELOG.md`).

Das Format richtet sich nach dem Changelog-Parser von shopware-cli (verwendet für den Upload in den Shopware
Store): eine `#`-Überschrift pro Version, die exakt der Version in `composer.json` entspricht, gefolgt von
einer flachen Liste der Änderungen.

# Unreleased

- Behoben: Der Befehl für den Health-Report stürzt nicht mehr ab.
- Behoben: Die Prüfung der geplanten Aufgaben ignoriert inaktive und übersprungene Aufgaben und meldet nur tatsächlich überfällige.
- Die unfertige Sitemap-Prüfung, durch die jeder Report fehlschlug, wurde entfernt.
- Der Health-Report enthält immer eine Prüfliste, auch wenn keine Prüfungen registriert sind.
