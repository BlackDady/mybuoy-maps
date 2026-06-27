#!/bin/bash



#############################
# FONCTION TestWay
#############################
TestWay1()
{
  MIN_LON=$1
  MIN_LAT=$2
  MAX_LON=$3
  MAX_LAT=$4

  echo "EXPLAIN QUERY PLAN "
  echo "SELECT "
  echo "w.way_id, w.min_lon, w.max_lon, w.min_lat, w.max_lat, "
  #echo "wt.key, wt.value, "
  echo "n.lon, n.lat "
  echo "FROM ways w"
  echo "INNER JOIN way_nodes wn ON w.way_id=wn.way_id"
  echo "INNER JOIN nodes n ON n.node_id=wn.node_id"
  #echo "LEFT JOIN way_tags wt ON wt.way_id=w.way_id"
  echo "WHERE min_lon>=$MIN_LON AND max_lon<=$MAX_LON "
  echo "AND min_lat>=$MIN_LAT AND max_lat<=$MAX_LAT "
  #echo "ORDER BY w.way_id, wt.key, n.node_id, wn.node_order"
  echo "ORDER BY w.way_id, wn.node_order"
  echo "LIMIT 10"
  echo ";"
}

#############################
# FONCTION TestWay
#############################
TestWay2()
{
  MIN_LON=$1
  MIN_LAT=$2
  MAX_LON=$3
  MAX_LAT=$4

  echo "EXPLAIN QUERY PLAN "
  echo "SELECT "
  echo "w.way_id, w.min_lon, w.max_lon, w.min_lat, w.max_lat, "
  #echo "wt.key, wt.value, "
  echo "n.lon, n.lat "
  echo "FROM ways w"
  echo "INNER JOIN way_nodes wn ON w.way_id=wn.way_id"
  echo "INNER JOIN nodes n ON n.node_id=wn.node_id"
  #echo "LEFT JOIN way_tags wt ON wt.way_id=w.way_id"
  echo "WHERE ( max_lon>=$MIN_LON AND max_lon<=$MAX_LON AND min_lat>=$MIN_LAT AND min_lat<=$MAX_LAT) "
  echo "OR    (min_lon>=$MIN_LON AND min_lon<=$MAX_LON AND min_lat>=$MIN_LAT AND min_lat<=$MAX_LAT) "
  #echo "ORDER BY w.way_id, wt.key, n.node_id, wn.node_order"
  echo "ORDER BY w.way_id, wn.node_order"
  echo "LIMIT 10"
  echo ";"
}

#############################
# FONCTION TestWay
#############################
TestWay3()
{
  MIN_LON=$1
  MIN_LAT=$2
  MAX_LON=$3
  MAX_LAT=$4

  echo "EXPLAIN QUERY PLAN "
  echo "SELECT "
  echo "w.way_id, w.min_lon, w.max_lon, w.min_lat, w.max_lat, "
  #echo "wt.key, wt.value, "
  echo "n.lon, n.lat "
  echo "FROM ways w"
  echo "INNER JOIN way_nodes wn ON w.way_id=wn.way_id"
  echo "INNER JOIN nodes n ON n.node_id=wn.node_id"
  #echo "LEFT JOIN way_tags wt ON wt.way_id=w.way_id"
  echo "WHERE ( max_lon>=$MIN_LON AND max_lon<=$MAX_LON AND min_lat>=$MIN_LAT AND min_lat<=$MAX_LAT) "
  echo "OR    (min_lon>=$MIN_LON AND min_lon<=$MAX_LON AND min_lat>=$MIN_LAT AND min_lat<=$MAX_LAT) "
  echo "OR    (min_lon>=$MIN_LON AND min_lon<=$MAX_LON AND max_lat>=$MIN_LAT AND max_lat<=$MAX_LAT) "
  echo "OR    (max_lon>=$MIN_LON AND max_lon<=$MAX_LON AND max_lat>=$MIN_LAT AND max_lat<=$MAX_LAT) "
  #echo "ORDER BY w.way_id, wt.key, n.node_id, wn.node_order"
  echo "ORDER BY w.way_id, wn.node_order"
  echo "LIMIT 10"
  echo ";"
}

#############################
# FONCTION TestWay
#############################
TestWay4()
{
  MIN_LON=$1
  MIN_LAT=$2
  MAX_LON=$3
  MAX_LAT=$4

  echo "EXPLAIN QUERY PLAN "
  echo "SELECT "
  echo "w.way_id, w.min_lon, w.max_lon, w.min_lat, w.max_lat, "
  #echo "wt.key, wt.value, "
  echo "n.lon, n.lat "
  echo "FROM ways w"
  echo "CROSS JOIN way_nodes wn ON w.way_id=wn.way_id"
  echo "INNER JOIN nodes n ON n.node_id=wn.node_id"
  #echo "LEFT JOIN way_tags wt ON wt.way_id=w.way_id"
  echo "WHERE ( max_lon>=$MIN_LON AND max_lon<=$MAX_LON AND min_lat>=$MIN_LAT AND min_lat<=$MAX_LAT) "
  echo "OR    (min_lon>=$MIN_LON AND min_lon<=$MAX_LON AND min_lat>=$MIN_LAT AND min_lat<=$MAX_LAT) "
  echo "OR    (min_lon>=$MIN_LON AND min_lon<=$MAX_LON AND max_lat>=$MIN_LAT AND max_lat<=$MAX_LAT) "
  echo "OR    (max_lon>=$MIN_LON AND max_lon<=$MAX_LON AND max_lat>=$MIN_LAT AND max_lat<=$MAX_LAT) "
  #echo "ORDER BY w.way_id, wt.key, n.node_id, wn.node_order"
  echo "ORDER BY w.way_id, wn.node_order"
  echo "LIMIT 10"
  echo ";"
}

#############################
# FONCTION TestWay
#############################
TestWay5()
{
  MIN_LON=$1
  MIN_LAT=$2
  MAX_LON=$3
  MAX_LAT=$4

  #echo "EXPLAIN QUERY PLAN "
  echo "SELECT w.way_id, w.way_nodes_count, "
  #echo "wn.node_order,"
  #echo "wt.key, wt.value, "
  echo "n.lon, n.lat "
  echo "FROM "

  echo "(SELECT "
  echo "w2.way_id AS way_id, count(w2.way_id) AS way_nodes_count"
  echo "FROM ways w2"
  echo "CROSS JOIN way_nodes wn2 ON w2.way_id=wn2.way_id"
  echo "WHERE ( max_lon>=$MIN_LON AND max_lon<=$MAX_LON AND min_lat>=$MIN_LAT AND min_lat<=$MAX_LAT) "
  echo "OR    (min_lon>=$MIN_LON AND min_lon<=$MAX_LON AND min_lat>=$MIN_LAT AND min_lat<=$MAX_LAT) "
  echo "OR    (min_lon>=$MIN_LON AND min_lon<=$MAX_LON AND max_lat>=$MIN_LAT AND max_lat<=$MAX_LAT) "
  echo "OR    (max_lon>=$MIN_LON AND max_lon<=$MAX_LON AND max_lat>=$MIN_LAT AND max_lat<=$MAX_LAT) "
  echo "OR    (min_lon<=$MIN_LON AND max_lon>=$MAX_LON AND min_lat<=$MIN_LAT AND max_lat>=$MAX_LAT) "
  echo "GROUP BY w2.way_id "
  echo "HAVING count(w2.way_id) < 500) w "

  echo "CROSS JOIN way_nodes wn ON w.way_id=wn.way_id"
  echo "INNER JOIN nodes n ON n.node_id=wn.node_id"
  #echo "LEFT JOIN way_tags wt ON wt.way_id=w.way_id"
  echo "ORDER BY w.way_id, wn.node_order"
  echo "LIMIT 100"
  echo ";"
}

#############################
# FONCTION TestWay
#############################
TestWay6()
{
  MIN_LON=$1
  MIN_LAT=$2
  MAX_LON=$3
  MAX_LAT=$4

  #echo "EXPLAIN QUERY PLAN "
  echo "SELECT w.way_id, w.way_nodes_count, "
  echo "w.min_lon, w.min_lat, w.max_lon, w.max_lat, "
  #echo "wn.node_order,"
  #echo "wt.key, wt.value, "
  echo "n.lon, n.lat "
  echo "FROM "

  echo "(SELECT "
  echo "w2.way_id AS way_id, count(w2.way_id) AS way_nodes_count, w2.min_lon AS min_lon, w2.min_lat AS min_lat, w2.max_lon AS max_lon, w2.max_lat AS max_lat "
  echo "FROM ways w2"
  echo "CROSS JOIN way_nodes wn2 ON w2.way_id=wn2.way_id"
  echo "WHERE ( max_lon>=$MIN_LON AND min_lon<=$MAX_LON AND max_lat>=$MIN_LAT AND min_lat<=$MAX_LAT) "
  echo "GROUP BY w2.way_id, w2.min_lon, w2.min_lat, w2.max_lon, w2.max_lat "
  echo "HAVING count(w2.way_id) < 100000) w "

  echo "CROSS JOIN way_nodes wn ON w.way_id=wn.way_id"
  echo "INNER JOIN nodes n ON n.node_id=wn.node_id"
  #echo "LEFT JOIN way_tags wt ON wt.way_id=w.way_id"
  echo "ORDER BY w.way_id, wn.node_order"
  echo "LIMIT 100"
  echo ";"
}


#############################
# FONCTION Main
#############################
Main()
{
  echo ".header on"
  echo ".mode tabs"

  TestWay1 "-4.0697" "47.6806" "-3.9111" "47.7878"
  TestWay2 "-4.0697" "47.6806" "-3.9111" "47.7878"
  TestWay3 "-4.0697" "47.6806" "-3.9111" "47.7878"
  TestWay4 "-4.0697" "47.6806" "-3.9111" "47.7878"
  #TestWay5 "-4.0697" "47.6806" "-3.9111" "47.7878"
  #TestWay5 "-4.0093" "47.7198" "-4.0011" "47.7254"
  #TestWay6 "-4.0093" "47.7198" "-4.0011" "47.7254"
  TestWay6 "-3.9769" "47.8918" "-3.9686" "47.8974"


}

#############################
# MAIN BASH
#############################
Main | sqlite3 land_polygons.sqlite3
