import React from 'react';

export const ItemImage = ({ item, showTitle = true }) => {
  const STORAGE_URL = "http://localhost:8095/storage/";

  const image = item.images?.[0];
  const imageVariant = image?.variants?.banner || image?.variants?.thumb;
  const imageUrl = imageVariant ? `${STORAGE_URL}${imageVariant}` : null;
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

      {imageUrl ? (
        <div
          className="relative w-full max-w-2xl aspect-video shrink-0 rounded-3xl shadow-sm overflow-hidden border border-slate-200/60"
          style={{ backgroundColor: dominantColor }}
        >
          <img
            src={imageUrl}
            alt="Fondo ambiental"
            className="absolute inset-0 w-full h-full object-cover blur-2xl opacity-50 scale-110"
          />

          <div className="absolute inset-0 bg-black/5 mix-blend-multiply" />

          <img
            src={imageUrl}
            alt={item.name}
            className="relative z-10 w-full h-full object-cover p-3 drop-shadow-xl"
          />
        </div>
      ) : (
        <div className="w-full max-w-2xl aspect-video shrink-0 bg-slate-100 rounded-3xl flex items-center justify-center border border-slate-200 shadow-inner p-4">
          <span className="text-slate-400 font-medium text-sm md:text-base">Sin imagen disponible</span>
        </div>
      )}

    </div>
  );
};