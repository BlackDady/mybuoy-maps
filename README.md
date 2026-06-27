# mybuoy-maps

Distribution des cartes **offline** de l'application MyBuoy.

Ce dépôt sert à mettre à disposition des testeurs (et de l'app) la base de données
`land_polygons` utilisée pour la détection terre/mer hors-ligne, sans dépendre de
l'hébergement mutualisé (trop petit pour ~4 Go).

## Contenu

- **Releases** → les bases `land_polygons` (SQLite avec index R*Tree, compressées en `.gz`),
  téléchargées et décompressées par l'app à l'installation.
- **[`generator/`](generator/)** → le code et le mode opératoire pour (re)générer la base
  à partir des *land polygons* OpenStreetMap. Voir [generator/README.md](generator/README.md).

## Comment l'app récupère la base

```
mybuoy.example.org/map-checkupdate.php   (manifest JSON : nom, version, taille, URL)
        │  l'app lit ce manifest (AppManager.downloadMapsInfos)
        ▼
GitHub Release de ce dépôt : land_polygons.sqlite3.gz   (le gros fichier)
        │  l'app télécharge (GBDownloader) + décompresse
        ▼
land_polygons_<version>.sqlite3 sur l'appareil → détecté par l'auto-scan, lu par Chunk.java
```

Le manifest pointe vers l'asset de release via son champ `Url`. La `Version` est propre à
ce dépôt (à incrémenter à chaque régénération de la base).

## Releases

| Version | Tag | Asset | Taille `.gz` | Décompressé |
|---------|-----|-------|--------------|-------------|
| 1 | [`maps-v1`](../../releases/tag/maps-v1) | `land_polygons.sqlite3.gz` | 2 019 622 371 o | 4 235 317 248 o |
