# Full Docker topology

- Laravel backend calls Nominatim via `NOMINATIM_URL`.
- Browser calls TileServer via `TILESERVER_URL`.
- Browser calls OSRM via `OSRM_URL`.

For local setup:

- `NOMINATIM_URL=http://localhost:8083`
- `TILESERVER_URL=http://localhost:8082`
- `OSRM_URL=http://localhost:5000`
