import React from 'react';
import StorageImage from '../common/StorageImage.jsx';

export const ItemImage = ({ item, showTitle = true, variant = 'banner' }) => {
  const image = item.images?.[0];
  const imageVariant = image?.variants?.[variant] || image?.variants?.[variant];

  // Si no hay color, usamos el equivalente a bg-slate-100 (#f1f5f9) para que los fallbacks se fusionen
  const dominantColor = image?.meta?.color || '#f1f5f9';

  return (
    <div className="flex flex-col items-center text-center w-full mt-8">

      {showTitle && (
        <div className="w-full max-w-xl shrink-0 mb-3 md:mb-4 px-2">
          <h2 className="text-xl md:text-2xl xl:text-3xl font-black text-slate-900 ">
            {item.name}
          </h2>
        </div>
      )}

      <div
        className="relative w-full max-w-2xl aspect-video shrink-0 rounded-3xl shadow-sm overflow-hidden border border-slate-200/60 bg-slate-100"
        style={{ backgroundColor: dominantColor }}
      >
        <StorageImage
          src={imageVariant}
          className="absolute inset-0 w-full h-full object-cover blur-2xl opacity-50 scale-110"
        />

        <div className="absolute inset-0 bg-black/5 mix-blend-multiply" />

        <StorageImage
          src={imageVariant}
          alt={item.name || 'Imagen del ítem'}
          className="relative z-10 w-full h-full object-cover p-3 drop-shadow-xl rounded-3xl"
        />
      </div>

    </div>
  );
};