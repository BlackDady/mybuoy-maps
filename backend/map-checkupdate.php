<?php
/**
 * map-checkupdate.php — manifest des maps offline de MyBuoy.
 *
 * Public, sans auth. Renvoie le JSON : { "Status":"OK", "Msg":..., "Map":[ {…} ] }
 * lu par l'app (AppManager.downloadMapsInfos -> GBAPIObject).
 *
 * Appel typique : GET https://<votre-domaine>/map-checkupdate.php
 * Paramètres optionnels : ?name=<map>&ver=<version> (pour le message up_to_date).
 */
require_once __DIR__ . '/include/gb_maps.php';

gb_cors();
header('Content-Type: application/json; charset=utf-8');

$maps = gb_maps_list(); // maps GitHub (codées en dur) + maps locales scannées dans ~/maps

// Optionnel : si l'app demande une map + version, signaler si elle est à jour.
$name = gb_param('name');
$ver  = gb_param('ver');
$ver  = ($ver !== '') ? intval($ver) : -1;

$msg = 'update';
if( $name !== '' ) {
    foreach( $maps as $m ) {
        if( strcasecmp($m['Name'], $name) === 0 && $m['Version'] <= $ver )
            $msg = 'up_to_date';
    }
}

echo json_encode(
    array('Status' => 'OK', 'Msg' => $msg, 'Map' => $maps),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
?>
