#!/usr/bin/env python3
# -*- coding:Utf-8 -*-
#shapefile2sqlite.py

# see https://osmdata.openstreetmap.de/data/land-polygons.html
# my interest is Land polygons - Format: Shapefile, Projection: WGS84 - with : Large polygons are split, use for larger scales
import shapefile # python3 -m pip install PyShp https://pypi.org/project/pyshp/ -  thanks for your code


import os
from pathlib import Path
import sqlite3
import time

def dbf4Read(filename):
    print( filename )
    
    sf = shapefile.Reader(filename)
    
    print(sf)
    print( sf.shapeType )
    print( len(sf) )
    print( sf.bbox )
    
    print( sf.shapes )
    
    shapes = sf.shapes()
    print( len(shapes) )
    for i in range (0, 3 ):
        shape = shapes[i]
        
        if shape.shapeType == 5: # 5 = POLYGON
            print( shape.bbox )
            print( len(shape.points) )
            for j in range (0,len(shape.points) ):
                print( shape.points[j] )
                
                print( f'<node id="" lat="{shape.points[j][0]}" lon="{shape.points[j][1]}" />' )

    
    #shapes = sf.shapes()
    #print( len(shapes) )
    
    
def shpRead(filename):
    print( filename )
    
    sf = shapefile.Reader(filename)
    
    print(sf)
    print( sf.shapeType )
    print( len(sf) )
    print( sf.bbox )
    #print( sf.shapes )
    #print( sf.shapes.len() )
    
    
def dbfx2sqllite(filename):
    
    
    
    basename_filename = os.path.basename(filename)
    basename_without_ext_filename = Path(filename).stem
    
    # start
    print(time.strftime('%H:%M:%S', time.localtime()), 'reading '+basename_filename+'...')
    
    filename_db = "./"+basename_without_ext_filename+".sqlite3"
    #print( filename_db)
    
     # delete old database file if exists
    if os.path.exists(filename_db):
        os.remove(filename_db)
        print(time.strftime('%H:%M:%S', time.localtime()), 'existing file '+filename_db+' removed')
        
    db_connect = sqlite3.connect(filename_db)
    db = db_connect.cursor()   # new database cursor
    
    db.execute('''
    CREATE TABLE nodes (
     node_id      INTEGER PRIMARY KEY,  -- node ID
     lon          REAL,                 -- longitude
     lat          REAL                  -- latitude
    )
    ''')
    
    #db.execute('''
    #CREATE TABLE ways (
    # way_id      INTEGER PRIMARY KEY,   -- way ID
    # min_lon          REAL,             -- minimum longitude area
    # min_lat          REAL,             -- minimum latitude area
    # max_lon          REAL,             -- maximum longitude area
    # max_lat          REAL              -- maximum latitude area
    #)
    #''')
    
    db.execute('''
    CREATE VIRTUAL TABLE ways USING rtree( way_id, min_lon, max_lon, min_lat, max_lat )
    ''')
    
    db.execute('''
    CREATE TABLE node_tags (
     node_id      INTEGER,              -- node ID
     key          TEXT,                 -- tag key
     value        TEXT                  -- tag value
    )
    ''')
    db.execute('''
    CREATE TABLE way_nodes (
     way_id       INTEGER,              -- way ID
     node_id      INTEGER,              -- node ID
     node_order   INTEGER               -- node order
    )
    ''')
    db.execute('''
    CREATE TABLE way_tags (
     way_id       INTEGER,              -- way ID
     key          TEXT,                 -- tag key
     value        TEXT                  -- tag value
    )
    ''')
    
    
    
    
    
    sf = shapefile.Reader(filename)
        
    shapes = sf.shapes()
    itemsCount = len(shapes)
    #itemsCount =  3
    
    current_way_id = 0
    current_node_id = 0
    way_node_order = 0
    
    
    for i in range (0, itemsCount ):
    
        if (not i % 1000) or ( i == (itemsCount-1)) :
            print(time.strftime('%H:%M:%S', time.localtime()), f"{i+1}/{itemsCount}")
            
        shape = shapes[i]
        if shape.shapeType == 5: # 5 = POLYGON  
        
            current_way_id += 1
            way_node_order = 0
            min_lon = 0
            min_lat = 0
            max_lon = 0
            max_lat = 0
            
            #print( shape.bbox )
            #print( len(shape.points) )   
            
            for j in range (0,len(shape.points)):
            
                current_node_id += 1
                way_node_order += 1
                
                [lon, lat] = shape.points[j] 
                #print( f"{lon} {lat}" )
                
                # could be the same values with shape.bbox
                if j == 0:
                    min_lon = lon
                    min_lat = lat
                    max_lon = lon
                    max_lat = lat
                else:
                    if lon < min_lon:
                        min_lon = lon                        
                    if lon > max_lon:
                        max_lon = lon
                    if lat < min_lat:
                        min_lat = lat                        
                    if lat > max_lat:
                        max_lat = lat
                    
                
                db.execute('INSERT INTO nodes (node_id,lon,lat) VALUES (?,?,?)',
                 (current_node_id, lon, lat))
                    
                #db.execute('INSERT INTO node_tags (node_id,key,value) VALUES (?,?,?)',
                # (current_node_id, 'shapefile_source', basename_without_ext_filename))
                 
                db.execute('INSERT INTO way_nodes (way_id,node_id,node_order) VALUES (?,?,?)',
                  (current_way_id, current_node_id, way_node_order))
                 
            
            db.execute('INSERT INTO ways (way_id,min_lon,max_lon,min_lat,max_lat) VALUES (?,?,?,?,?)',
             (current_way_id, min_lon, max_lon, min_lat, max_lat))
                     
            #db.execute('INSERT INTO way_tags (way_id,key,value) VALUES (?,?,?)',
            # (current_way_id, 'shapefile_source', basename_without_ext_filename))
                 
            #db.execute('INSERT INTO way_tags (way_id,key,value) VALUES (?,?,?)',
            # (current_way_id, 'natural', 'coastline'))
                     
               
                
    # write data to database
    db_connect.commit()
    
    if True:
        # Create Indexes
        print(time.strftime('%H:%M:%S', time.localtime()), 'creating index...')
        #db.execute('CREATE INDEX node_tags__node_id ON node_tags (node_id)')
        #db.execute('CREATE INDEX node_tags__key     ON node_tags (key)')
        #db.execute('CREATE INDEX way_tags__way_id   ON way_tags (way_id)')
        #db.execute('CREATE INDEX way_tags__key      ON way_tags (key)')
        
        #db.execute('CREATE INDEX way_nodes__way_id  ON way_nodes (way_id)')
        #db.execute('CREATE INDEX way_nodes__node_id ON way_nodes (node_id)')
        db.execute('CREATE INDEX way_nodes__node_id__way_id__node_order ON way_nodes (way_id, node_order)')
        
        #db.execute('CREATE INDEX relation_members__relation_id ON relation_members ( relation_id )')
        #db.execute('CREATE INDEX relation_members__type        ON relation_members ( type, ref )')
        #db.execute('CREATE INDEX relation_tags__relation_id    ON relation_tags ( relation_id )')
        #db.execute('CREATE INDEX relation_tags__key            ON relation_tags ( key )')
        db_connect.commit()
    
    
    # finish
    print(time.strftime('%H:%M:%S', time.localtime()), 'finished')
    


if __name__ == '__main__':
    filename_dbf = './land-polygons-split-4326/land_polygons.dbf'
    filename_shp = './land-polygons-split-4326/land_polygons.shp'
    
    #shpRead(filename_shp)
    #dbf4Read(filename_dbf)
    
    dbfx2sqllite( filename_dbf )
    
    
   
    
    
    
