import React, { useState, useEffect } from 'react';
import StorageImage from '../ui/StorageImage.jsx';
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import ItemImageCredit from './ItemImageCredit.jsx';
import { pickImageVariant } from './ItemImage.jsx';
import { getCommonsImageCredit } from '../../lib/attribution.js';

// Póster de la ficha: se queda fijo mientras se desplaza el texto y nunca se amplía por encima de su tamaño natural.
const ItemDetailPoster = ({ item }) => {
  const image = item.images?.[0];
  const src = pickImageVariant(image);

  // Si no hay imagen o falla su carga, se muestra el placeholder generativo.
  const [failed, setFailed] = useState(false);
  useEffect(() => {
    setFailed(false);
  }, [src]);
  const showPlaceholder = !src || failed;

  // La proporción de la imagen (2/3 si no se conoce) hace que se vea entera; el ancho natural evita ampliarla.
  const aspectRatio = image?.meta?.aspect_ratio || '2 / 3';
  const width = image?.meta?.width;
  const maxWidth = typeof width === 'number' && width > 0 ? `${width}px` : undefined;

  return (
    <figure className="mx-auto w-full max-w-64 lg:sticky lg:top-sticky-offset lg:max-w-none">
      <div
        data-detail-poster
        className="mx-auto overflow-hidden rounded-md border border-line bg-surface"
        style={{ aspectRatio, maxWidth }}
      >
        {showPlaceholder ? (
          <ItemPlaceholder
            name={item.name}
            categorySlug={item.category?.slug}
            categoryIcon={item.category?.icon}
            variant="banner"
            decorative
            className="h-full w-full"
          />
        ) : (
          <StorageImage
            src={src}
            alt={item.name || 'Imagen del ítem'}
            loading="eager"
            className="h-full w-full object-cover"
            onError={() => setFailed(true)}
          />
        )}
      </div>
      {!showPlaceholder && getCommonsImageCredit(image) && (
        <figcaption className="mt-2">
          <ItemImageCredit image={image} />
        </figcaption>
      )}
    </figure>
  );
};

export default ItemDetailPoster;
