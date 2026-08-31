<?php
/**
 * map-dl.php — téléchargement d'une map AVEC reprise (HTTP Range / 206).
 *
 * Public, sans auth. NON utilisé tant que les maps sont servies par GitHub.
 * Prévu pour un futur hébergement web : place les fichiers
 * "<name>_<version>.<ext>" (ex. land_polygons_1.sqlite3.gz) dans GB_MAPS_DIR,
 * et pointe le champ Url du manifest vers map-dl.php?name=<name>&ver=<version>.
 *
 * Supporte GET (téléchargement, avec Range) et HEAD (taille + Accept-Ranges),
 * ce qu'attend le downloader de l'app (HEAD puis GET avec reprise).
 */
require_once __DIR__ . '/../include/gb_maps.php';

gb_cors();

$name = gb_param('name');
$ver  = gb_param('ver');
$ver  = ($ver !== '') ? intval($ver) : -1;

if( $name === '' ) { header('HTTP/1.1 400 Bad Request'); exit; }

// Recherche du fichier "<name>_<version>.<ext>" dans GB_MAPS_DIR (parseur partagé).
// Si aucune version n'est demandée (ver < 0), on sert la PLUS RÉCENTE.
$file = null;
$file_ver = -1;
if( is_dir(GB_MAPS_DIR) ) {
    foreach( scandir(GB_MAPS_DIR) as $f ) {
        $path = GB_MAPS_DIR . $f;
        if( !is_file($path) ) continue;

        $p = gb_parse_map_filename($f);
        if( $p === null || strcasecmp($p['name'], $name) !== 0 ) continue;

        if( $ver >= 0 ) {
            if( $p['version'] === $ver ) { $file = $path; $file_ver = $p['version']; break; }
        } else if( $p['version'] > $file_ver ) {
            $file = $path; $file_ver = $p['version']; // garde la plus récente
        }
    }
}

if( $file === null || !file_exists($file) ) {
    header('HTTP/1.1 404 Not Found');
    exit;
}

// En-têtes de type / nom de fichier.
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
switch( $ext ) {
    case 'gz':  header('Content-Type: application/gzip'); break;
    case 'zip': header('Content-Type: application/zip');  break;
    default:    header('Content-Type: application/octet-stream'); break;
}
$dlname = basename($file); // ex. france_002.sqlite3.gz
header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($dlname));
header('Last-Modified: ' . gmdate(DATE_RFC1123, filemtime($file)));

// HEAD : uniquement les en-têtes (taille + accept-ranges), pas de corps.
if( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'HEAD' ) {
    header('Accept-Ranges: bytes');
    header('Content-Length: ' . filesize($file));
    exit;
}

// GET : envoi avec reprise.
gb_range_download($file);
?>
