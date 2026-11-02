import React, { Suspense, lazy } from 'react';

// Leaflet y react-leaflet solo se descargan en las fichas con coordenadas (destinos, F4-03): van en un chunk aparte.
const PlaceMap = lazy(() => import('./PlaceMap.jsx'));

const MAP_HEIGHT = 'h-64 sm:h-80';

// Si el chunk del mapa no carga (sin conexión o tras un despliegue), la ficha sigue sin la sección del mapa.
class PlaceMapErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError() {
    return { hasError: true };
  }

  componentDidCatch(error) {
    console.error('No se ha podido cargar el mapa: ', error);
  }

  render() {
    return this.state.hasError ? null : this.props.children;
  }
}

// Sección "Ubicación" de la ficha: solo se monta si el ítem tiene coordenadas válidas.
const ItemPlaceMap = ({ name, coordinates, zoom, className = '' }) => (
  <PlaceMapErrorBoundary>
    <section aria-label={`Mapa de ${name}`} className={`flex flex-col gap-3 ${className}`}>
      <h3 className="text-lg font-bold text-slate-800">Ubicación</h3>
      <Suspense fallback={<div aria-hidden="true" className={`${MAP_HEIGHT} w-full animate-pulse rounded-2xl bg-slate-100`} />}>
        <PlaceMap coordinates={coordinates} zoom={zoom} className={MAP_HEIGHT} />
      </Suspense>
    </section>
  </PlaceMapErrorBoundary>
);

export default ItemPlaceMap;
