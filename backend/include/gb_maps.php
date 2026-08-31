<?php
/**
 * gb_maps.php — include UNIQUE du backend "maps" de MyBuoy.
 *
 * Version simplifiée d'un backend "maps" interne d'origine
 * (map-checkupdate.php / map-dl.php + plusieurs includes), tout regroupé ici.
 *
 * Choix assumés :
 *   - AUCUNE sécurité : accès 100 % public, pas d'auth, pas de session, pas de cookie,
 *     pas de redirection HTTPS, pas de vérification SSL.
 *   - Pas de base de données : la liste des maps est STATIQUE (cf. gb_maps_list()).
 *   - Tout le nécessaire est regroupé ici (CORS, params, manifest, download avec reprise).
 *
 * Format de sortie consommé tel quel par l'app Android
 * (GBAPIObject / MapInfo, champs @SerializedName : Title, Name, Version, FileSize, Url).
 */

// ===========================================================================
//  >>> SEUL endroit à modifier si besoin : domaine du backend. <<<
//  Utilisé UNIQUEMENT en repli hors-web (CLI). En accès web normal, l'URL est
//  auto-détectée via $_SERVER['HTTP_HOST'] -> rien à changer ici.
//  to change eg.: https://mybuoy.example.org
// ===========================================================================
if( !defined('GB_SITE_URL') )
    define('GB_SITE_URL', '<votre-domaine>');


// ===========================================================================
//  CONFIG — liste statique des maps publiées.
//  Pour ajouter / mettre à jour une map : éditer ce tableau.
//  FileSize = taille du fichier .gz en octets. Url = lien de téléchargement direct.
// ===========================================================================
function gb_maps_list() {
    // 1) Maps codées en dur, hébergées sur GitHub (téléchargement direct).
    $hardcoded = array(
        array(
            'Title'    => 'Land polygons (monde)',
            'Name'     => 'land_polygons',
            'Version'  => 1,
            'FileSize'       => 4235317248, // .sqlite3 décompressé (la base)
            'CompressedSize' => 2019622371, // .gz téléchargé
            'Url'            => 'https://github.com/BlackDady/mybuoy-maps/releases/download/maps-v1/land_polygons.sqlite3.gz',
        ),
    );

    // 2) Maps découvertes dans GB_MAPS_DIR, servies par map-dl.php (avec reprise).
    $scanned = gb_scan_maps_dir(gb_base_url());

    // Concaténation : dur (GitHub) + scannées (local).
    return array_merge($hardcoded, $scanned);
}

// Dossier des fichiers maps servis par map-dl.php (avec reprise).
// Placé à la RACINE DU COMPTE (~/maps), un cran AU-DESSUS du docroot web,
// donc NON téléchargeable directement par URL (uniquement via map-dl.php).
if( !defined('GB_MAPS_DIR') ) {
    $gb_home = (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '')
             ? dirname($_SERVER['DOCUMENT_ROOT'])   // <docroot> (.../www) -> son parent
             : dirname(dirname(dirname(__DIR__)));   // fallback CLI : include -> mybuoy -> www -> home
    define('GB_MAPS_DIR', $gb_home . '/maps/');
}


// ===========================================================================
//  CORS — tout public.
// ===========================================================================
function gb_cors() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
    header('Access-Control-Allow-Headers: Range, If-Range, Content-Type');
    header('Access-Control-Expose-Headers: Accept-Ranges, Content-Length, Content-Range');
}


// ===========================================================================
//  Lecture d'un paramètre GET/POST (remplace RequestParameter()).
// ===========================================================================
function gb_param($name, $default = '') {
    if( isset($_GET[$name]) )  return $_GET[$name];
    if( isset($_POST[$name]) ) return $_POST[$name];
    return $default;
}


// ===========================================================================
//  URL de base où résident les scripts appelés (le dossier api/), pour construire
//  les liens map-dl.php,
//  auto-déduite de la requête. Hors-web (CLI) : repli sur GB_SITE_URL.
//  Ex. : https://mybuoy.example.org/mybuoy/api
// ===========================================================================
function gb_base_url() {
    if( !empty($_SERVER['HTTP_HOST']) ) {
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        $dir    = isset($_SERVER['SCRIPT_NAME']) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') : '';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir;
    }
    return rtrim(GB_SITE_URL, '/'); // repli hors-web (CLI)
}


// ===========================================================================
//  Décompose "<name>_<version>.<ext...>" -> array('name'=>…, 'version'=>int).
//  Gère les noms avec '_' (land_polygons) et les versions zéro-paddées (001),
//  ainsi que les doubles extensions (.sqlite3.gz). Retourne null si non conforme.
// ===========================================================================
function gb_parse_map_filename($filename) {
    if( preg_match('/^(.+)_(\d+)\..+$/', $filename, $m) )
        return array('name' => $m[1], 'version' => intval($m[2]));
    return null;
}


// ===========================================================================
//  Taille décompressée d'un .gz : footer gzip (4 derniers octets = ISIZE,
//  little-endian, modulo 2^32 -> valable pour un décompressé < 4 Go).
// ===========================================================================
function gb_gz_inflated_size($path) {
    $fp = @fopen($path, 'rb');
    if( $fp === false ) return 0;
    fseek($fp, -4, SEEK_END);
    $data = fread($fp, 4);
    fclose($fp);
    if( $data === false || strlen($data) < 4 ) return 0;
    $u = unpack('V', $data); // uint32 little-endian
    return $u[1];
}


// ===========================================================================
//  Génère la liste des maps en scannant GB_MAPS_DIR pour des fichiers
//  "<name>_<version>.<ext>". Reprend la logique de l'ancien _Map() / GBNET_EncodeMap :
//    - groupement par NOM (une map = plusieurs versions),
//    - tri des versions par ordre décroissant,
//    - on n'expose que la version la PLUS RÉCENTE dans le manifest.
//  Url -> map-dl.php?name=<name>&ver=<version>.
//  $base_url : ex. "https://mybuoy.example.org/mybuoy".
// ===========================================================================
function gb_cmp_version_desc($a, $b) {
    return $b['version'] - $a['version']; // version la plus haute en premier
}

function gb_scan_maps_dir($base_url) {
    if( !is_dir(GB_MAPS_DIR) ) return array();

    // 1) Regroupe les fichiers par nom de map (une map peut avoir N versions).
    $by_name = array(); // name => [ ['version'=>int,'filesize'=>int], ... ]
    foreach( scandir(GB_MAPS_DIR) as $file ) {
        $path = GB_MAPS_DIR . $file;
        if( !is_file($path) ) continue;

        $p = gb_parse_map_filename($file);
        if( $p === null ) continue;

        $csize = filesize($path);                                       // fichier sur disque (.gz le plus souvent)
        $ext   = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $dsize = ($ext === 'gz') ? gb_gz_inflated_size($path) : $csize; // base décompressée (== fichier si non-gz)
        $by_name[$p['name']][] = array(
            'version'        => $p['version'],
            'filesize'       => $dsize,   // base décompressée
            'compressedsize' => $csize,   // .gz
        );
    }

    // 2) Pour chaque map, on garde la version la plus récente pour le manifest.
    $maps = array();
    foreach( $by_name as $name => $versions ) {
        usort($versions, 'gb_cmp_version_desc');
        $latest = $versions[0];
        $maps[] = array(
            'Title'    => ucwords(str_replace('_', ' ', $name)),
            'Name'     => $name,
            'Version'        => $latest['version'],
            'FileSize'       => $latest['filesize'],        // base décompressée
            'CompressedSize' => $latest['compressedsize'],  // .gz
            'Url'            => rtrim($base_url, '/') . '/map-dl.php?name=' . rawurlencode($name) . '&ver=' . $latest['version'],
        );
    }
    return $maps;
}


// ===========================================================================
//  Téléchargement d'un fichier avec REPRISE (HTTP Range / 206 Partial Content).
//  Repris de rangeDownload() d'origine, nettoyé (bug "$range0" corrigé en $range[0]).
//  Retourne true si le fichier a été servi jusqu'à la fin.
// ===========================================================================
function gb_range_download($file) {
    $fp = @fopen($file, 'rb');
    if( $fp === false ) { header('HTTP/1.1 404 Not Found'); return false; }

    $size   = filesize($file);
    $start  = 0;
    $end    = $size - 1;
    $length = $size;

    header('Accept-Ranges: bytes');

    if( isset($_SERVER['HTTP_RANGE']) ) {
        $c_start = $start;
        $c_end   = $end;

        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);

        // Plages multiples non supportées.
        if( strpos($range, ',') !== false ) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            fclose($fp); exit;
        }

        if( $range[0] == '-' ) {
            // Suffixe : les N derniers octets.
            $c_start = $size - intval(substr($range, 1));
        } else {
            $parts   = explode('-', $range);
            $c_start = intval($parts[0]);
            $c_end   = (isset($parts[1]) && is_numeric($parts[1])) ? intval($parts[1]) : $end;
        }

        $c_end = ($c_end > $end) ? $end : $c_end;
        if( $c_start > $c_end || $c_start > $size - 1 || $c_end >= $size ) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            fclose($fp); exit;
        }

        $start  = $c_start;
        $end    = $c_end;
        $length = $end - $start + 1;
        fseek($fp, $start);
        header('HTTP/1.1 206 Partial Content');
    }

    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: $length");

    $buffer = 1024 * 8;
    while( !feof($fp) && ($p = ftell($fp)) <= $end ) {
        if( $p + $buffer > $end )
            $buffer = $end - $p + 1;      // ne pas dépasser la plage demandée
        set_time_limit(0);                // pas de timeout sur gros fichiers
        echo fread($fp, $buffer);
        flush();
    }
    fclose($fp);

    return ( $end >= $size - 1 );
}
?>
