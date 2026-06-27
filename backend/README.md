# backend/ — manifest & téléchargement des maps

Backend PHP minimal qui sert le **manifest** des maps offline à l'app MyBuoy.

Conçu pour être déployé dans un **sous-dossier d'un site** (ex. `www/mybuoy/`) →
**`https://mybuoy.example.org/mybuoy/`**. *(Le sous-dossier est utilisé quand l'offre
d'hébergement ne permet pas un sous-domaine dédié / multisite.)*

Version **simplifiée** d'un backend interne d'origine : **toute la sécurité a été retirée**
(accès 100 % public, pas d'authentification, pas de session/cookie, pas de redirection HTTPS,
pas de vérification SSL), et **toutes les dépendances sont regroupées dans un seul include**
(`include/gb_maps.php`).

> Le domaine n'est codé nulle part en dur dans la logique : l'URL est auto-déduite de
> `$_SERVER['HTTP_HOST']`. Le seul point de config (repli hors-web) est la constante
> `GB_SITE_URL` en haut de `include/gb_maps.php`.

## Fichiers

| Fichier | Rôle |
|---------|------|
| `map-checkupdate.php` | Renvoie le manifest JSON `{Status, Msg, Map:[…]}`. **Seul endpoint interrogé par l'app.** |
| `map-dl.php` | Télécharge un fichier de `GB_MAPS_DIR` avec **reprise** (HTTP Range) ; gère `HEAD`. |
| `include/gb_maps.php` | Include unique : config, CORS, params, liste des maps (dur + scan), download avec reprise. |

## Sources des maps (deux mécanismes, concaténés)

`gb_maps_list()` renvoie l'union de :

1. **Maps codées en dur** — hébergées sur **GitHub Releases**, `Url` = lien direct
   (ex. `land_polygons`, ~4 Go → trop gros pour un petit hébergement mutualisé).
2. **Maps scannées** dans **`GB_MAPS_DIR`** par `gb_scan_maps_dir()` — fichiers
   `<name>_<version>.<ext>` (ex. `france_002.sqlite3.gz`), `Url` = `map-dl.php?name=…&ver=…`.
   Groupement par nom + multi-version : seule la **version la plus récente** est exposée.

`GB_MAPS_DIR` pointe sur **`~/maps/`** (racine du compte, **au-dessus du docroot web**) : les
fichiers n'y sont donc **pas téléchargeables directement** par URL, uniquement via `map-dl.php`.

## Déploiement (via SFTP)

```
www/mybuoy/map-checkupdate.php      -> https://mybuoy.example.org/mybuoy/map-checkupdate.php
www/mybuoy/map-dl.php               -> https://mybuoy.example.org/mybuoy/map-dl.php
www/mybuoy/include/gb_maps.php      (inclus, non appelé directement)
maps/                               (= ~/maps, hors docroot : fichiers <name>_<version>.<ext>)
```

> L'app pointe sur `https://<votre-domaine>/mybuoy/map-checkupdate.php`
> (cf. `AppManager.downloadMapsInfos`).

## Mettre à jour / ajouter une map

- **Sur GitHub** : éditer le tableau en dur dans `gb_maps_list()` (`Title`, `Name`,
  `Version`, `FileSize` = taille du `.gz` en octets, `Url`).
- **Sur l'hébergement** : déposer `~/maps/<name>_<version>.<ext>` — détecté automatiquement
  (incrémenter la version dans le nom de fichier suffit).

## Manifest produit (exemple)

```json
{
  "Status": "OK",
  "Msg": "update",
  "Map": [
    { "Title": "Land polygons (monde)", "Name": "land_polygons", "Version": 1,
      "FileSize": 2019622371,
      "Url": "https://github.com/BlackDady/mybuoy-maps/releases/download/maps-v1/land_polygons.sqlite3.gz" },
    { "Title": "France", "Name": "france", "Version": 2, "FileSize": 1716,
      "Url": "https://mybuoy.example.org/mybuoy/map-dl.php?name=france&ver=2" },
    { "Title": "Israel", "Name": "israel", "Version": 1, "FileSize": 1716,
      "Url": "https://mybuoy.example.org/mybuoy/map-dl.php?name=israel&ver=1" }
  ]
}
```

> Les entrées `france` / `israel` ci-dessus sont des **fakes de test** (copies gzippées d'un
> petit `land_polygons.sqlite3` d'exemple) servant à valider le scan multi-version + `map-dl.php`.
