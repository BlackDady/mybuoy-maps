--
--
--
pragma temp_store = 1;
pragma temp_store_directory = './tmp';   -- dossier temporaire local (adapter si besoin)


CREATE VIRTUAL TABLE vways USING rtree( way_id, min_lat, max_lat, min_lon, max_lon );

INSERT INTO vways (way_id,min_lat,       max_lat,       min_lon,       max_lon)
SELECT      way_nodes.way_id,min(nodes.lat),max(nodes.lat),min(nodes.lon),max(nodes.lon)
FROM      way_nodes
LEFT JOIN nodes     ON nodes.node_id=way_nodes.node_id
GROUP BY way_nodes.way_id
;
