import React from 'react';
import { MapContainer, TileLayer, CircleMarker } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';

// Tiles estándar de OpenStreetMap, sin geocodificación ni Nominatim: las coordenadas vienen de Wikidata (P625).
// La atribución es obligatoria (https://operations.osmfoundation.org/policies/tiles/) y la pinta el control por defecto de Leaflet.
const OSM_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
const OSM_ATTRIBUTION = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
const OSM_MAX_ZOOM = 19;
// Círculo en lugar del marcador por defecto: no depende de las imágenes de Leaflet, que el bundler no resuelve.
const MARKER_STYLE = { color: '#2563eb', fillColor: '#2563eb', fillOpacity: 0.5, weight: 2 };

// Mapa de un destino (F4-03). Se carga con React.lazy desde ItemPlaceMap: Leaflet va en su propio chunk.
// isolate encierra los z-index de Leaflet (400–1000) para que no tapen la cabecera ni los modales (z-50).
const PlaceMap = ({ coordinates, zoom, className = '' }) => {
  const center = [coordinates.lat, coordinates.lng];

  return (
    <div data-place-map className={`isolate overflow-hidden rounded-md border border-line ${className}`}>
      <MapContainer center={center} zoom={zoom} scrollWheelZoom={false} className="h-full w-full">
        <TileLayer url={OSM_TILE_URL} attribution={OSM_ATTRIBUTION} maxZoom={OSM_MAX_ZOOM} />
        <CircleMarker center={center} radius={8} pathOptions={MARKER_STYLE} />
      </MapContainer>
    </div>
  );
};

export default PlaceMap;
