# Générateur de la base `land_polygons`

Génère la base SQLite `land_polygons.sqlite3` (avec index spatial R*Tree) utilisée par
MyBuoy pour la détection terre/mer hors-ligne, à partir des *land polygons* OpenStreetMap.

> ⚠️ Script repris tel quel depuis le dépôt interne `osm2sqlite` — chemins encore en dur,
> à rendre paramétrable (voir « À améliorer » en bas).

## 1. Données source

Land polygons OSM : https://osmdata.openstreetmap.de/data/land-polygons.html
(données dérivées des ways OSM taggés `natural=coastline`, régénérées régulièrement).

Télécharger : **Land polygons** · Format **Shapefile** · Projection **WGS84 (EPSG:4326)** ·
variante **« Large polygons are split »** → dossier `land-polygons-split-4326`.

> ⚠️ Le `4326` du nom de dossier **n'est PAS un numéro de build** : c'est le **code EPSG du
> système de coordonnées WGS84** (lon/lat en degrés). L'autre projection proposée est `3857`
> (Web Mercator, en mètres). On prend le **4326** car l'app stocke/interroge des lon/lat en
> degrés (`nodes.lon`/`nodes.lat`, requête bbox de `dbBBox()`) — du Mercator ne collerait pas.
> Le dataset OSM n'a pas de numéro de version dans le nom : la `Version` du manifest est la
> **nôtre**, à incrémenter à chaque régénération.

Les 5 variantes proposées par la page (on utilise la **n°2**) :

| # | Variante | Projection | Découpe |
|---|----------|------------|---------|
| 1 | Complete   | WGS84 (4326)    | non découpé |
| **2** | **Split** | **WGS84 (4326)** | **découpé** ← *la nôtre* |
| 3 | Complete   | Mercator (3857) | non découpé |
| 4 | Split      | Mercator (3857) | découpé |
| 5 | Simplified | Mercator (3857) | simplifié (zoom 0-9) |

Décompresser l'archive de façon à obtenir, à côté du script :
```
generator/
├── shapefile2sqlite.py
├── vways.sql                  # étape optionnelle (voir §3bis)
├── land.sh                    # harnais de test (voir §5)
└── land-polygons-split-4326/
    ├── land_polygons.shp
    ├── land_polygons.dbf
    └── ...
```

> 📌 La base de prod est générée dans un dossier de travail local (le `shapefile2sqlite.py`
> y est lancé). Taille non compressée de référence : 4 235 317 248 o.

## 2. Dépendances

```bash
python3 -m pip install pyshp        # module 'shapefile'
```

## 3. Génération

```bash
cd generator
python3 shapefile2sqlite.py         # lit ./land-polygons-split-4326/land_polygons.{shp,dbf}
```

Produit `land_polygons.sqlite3` dans le dossier courant. Sur le dataset mondial complet :
≈ **3,94 Go** (4 235 317 248 octets).

Schéma produit (consommé par `Chunk.java` côté app) :

| Table       | Type    | Colonnes |
|-------------|---------|----------|
| `nodes`     | table   | `node_id` PK, `lon`, `lat` |
| `ways`      | R*Tree  | `way_id`, `min_lon`, `max_lon`, `min_lat`, `max_lat` |
| `way_nodes` | table   | `way_id`, `node_id`, `node_order` (+ index `way_id, node_order`) |
| `node_tags`, `way_tags` | tables | créées vides (réservé) |

## 3bis. Index spatial — déjà fait, `vways.sql` NON nécessaire

L'index spatial **est déjà construit par `shapefile2sqlite.py`** : la table `ways` est une
`VIRTUAL TABLE ... USING rtree(way_id, min_lon, max_lon, min_lat, max_lat)` peuplée avec la
bbox de chaque polygone. **C'est cette table `ways` que l'app interroge** (`Chunk.java`,
requête `CROSS JOIN way_nodes` — le `CROSS JOIN` force l'usage de l'index R*Tree).

➡️ Le script **`vways.sql`** (qui crée une table R*Tree `vways` recalculée depuis `nodes`)
**n'est utilisé nulle part dans MyBuoy** — étape **optionnelle**, pas requise pour publier.
Application (pour info) :
```bash
sqlite3 land_polygons.sqlite3 < vways.sql
```

## 4. Tester la base — `land.sh`

Harnais de test/benchmark : génère plusieurs variantes de requêtes spatiales (`TestWay1..6`,
avec `EXPLAIN QUERY PLAN`) sur une bbox d'exemple (zone des Glénans) et les exécute via la CLI
`sqlite3`. `TestWay6` correspond à la requête réellement utilisée par l'app.
```bash
cd /path/to/maps      # là où se trouve land_polygons.sqlite3
bash land.sh          # se termine par : Main | sqlite3 land_polygons.sqlite3
```
> Chemins en dur (bbox, nom de base) — à paramétrer plus tard.

## 5. Compression + publication

L'app télécharge le `.gz` et le décompresse à l'installation :

```bash
gzip -k land_polygons.sqlite3       # -> land_polygons.sqlite3.gz  (≈ 1,88 Gio, ratio ~52 %)
```

Puis publier le `.gz` en **GitHub Release** de ce dépôt (asset < 2 Gio).
Le manifest servi par `mybuoy.example.org/mybuoy/api/map-checkupdate.php` pointe vers cet asset
(champ `Url`), avec `Name: land_polygons`, `Version: <N>`, `FileSize: <taille décompressée>`,
`CompressedSize: <taille du .gz>`.

> Côté app, le fichier installé est nommé `land_polygons_<Version>.sqlite3` (l'auto-scan le
> retrouve) — le nom interne du `.gz` n'a aucune importance.

## Version actuellement publiée

| Version | Asset | Taille .gz | Taille décompressée |
|---------|-------|-----------|---------------------|
| 1 | [`maps-v1`](../../releases/tag/maps-v1) → `land_polygons.sqlite3.gz` | 2 019 622 371 o | 4 235 317 248 o |

## À améliorer (chantier commun)

- [ ] Rendre les chemins d'entrée/sortie paramétrables (arguments CLI au lieu du code en dur, l. 230-231).
- [ ] Optionnel : `gzip` automatique en fin de script.
- [ ] Optionnel : générer/incrémenter la `Version` et calculer `FileSize`/sha256 pour le manifest.
