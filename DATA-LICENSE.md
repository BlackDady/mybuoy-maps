# Map data license (ODbL)

The offline map databases produced and distributed by this project
(`land_polygons.sqlite3`, served via GitHub Releases) are **derived from
OpenStreetMap land polygons**:
https://osmdata.openstreetmap.de/data/land-polygons.html

- **Data:** © OpenStreetMap contributors
- **License:** Open Database License (ODbL) v1.0 —
  https://opendatacommons.org/licenses/odbl/1-0/
- **More info:** https://www.openstreetmap.org/copyright

## What this means

These databases are a **Derivative Database** under the ODbL, distributed
under the same ODbL terms. You are free to use, share and adapt them, provided
that you:

1. **Attribute** OpenStreetMap contributors,
2. keep any **publicly distributed derivative database** under the ODbL, and
3. do not apply technical measures (DRM) that restrict others from obtaining
   the database.

The transformation from the OSM land-polygon shapefiles to the MyBuoy SQLite
format is documented in `generator/README.md`; the resulting database keeps
only geometry (land polygons) plus a spatial index — no other data source is
introduced.

> This ODbL notice covers the **data** only. The scripts and backend code in
> this repository are covered separately by [LICENSE](LICENSE).
