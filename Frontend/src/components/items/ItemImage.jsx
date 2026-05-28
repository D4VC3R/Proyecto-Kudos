import React from 'react';
import StorageImage from '../common/StorageImage.jsx';

export const ItemImage = ({ item, showTitle = true, variant = 'banner' }) => {
  const image = item.images?.[0];
  const imageVariant = image?.variants?.[variant] || image?.variants?.[variant];

  // Extraemos la metadata o definimos valores seguros por defecto
  const metaColor = image?.meta?.color || 'f1f5f9';
  const dominantColor = metaColor.startsWith('#') ? metaColor : `#${metaColor}`;

  const orientation = image?.meta?.orientation || 'landscape';
  const aspectRatio = image?.meta?.aspect_ratio || (orientation === 'portrait' ? 3/4 : 16/9);

  // Limitamos el ancho en función de la orientación para un escalado inteligente
  const maxWidthClass =
    orientation === 'portrait' ? 'max-w-[280px] sm:max-w-sm' :
      orientation === 'square' ? 'max-w-md' :
        'max-w-2xl';

  return (
    <div className="flex flex-col items-center justify-center w-full h-full min-h-0 text-center">
      {showTitle && (
        <div className="w-full max-w-xl shrink-0 mb-3 md:mb-4 px-2">
          <h2 className="text-xl md:text-2xl xl:text-3xl font-black text-text-highlight leading-tight">
            {item.name}
          </h2>
        </div>
      )}

      <div
        className={`relative w-full shrink-0 rounded-3xl shadow-sm overflow-hidden border border-border/60 ${maxWidthClass} max-h-full`}
        style={{
          backgroundColor: dominantColor,
          aspectRatio: aspectRatio
        }}
      >

        <StorageImage
          src={imageVariant}
          className="absolute inset-0 w-full h-full object-cover blur-2xl opacity-50 scale-110"
        />

        <div className="absolute inset-0 bg-black/5 mix-blend-multiply" />

        <StorageImage
          src={imageVariant}
          alt={item.name || 'Imagen del ítem'}
          className="relative z-10 w-full h-full object-contain drop-shadow-xl"
        />
      </div>
    </div>
  );
};