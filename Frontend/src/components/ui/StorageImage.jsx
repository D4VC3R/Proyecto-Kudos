import { useState, useEffect } from 'react';
import { ImageOff } from 'lucide-react';
import { getStorageUrl } from '../../lib/formatters.js';

const StorageImage = ({src, alt = "Imagen", className = "", fallbackIcon: FallbackIcon = ImageOff, fallback, onError, ...props}) => {
  const initialSrc = getStorageUrl(src);
  // Si de entrada no hay un src válido, marcamos el error como true directamente
  const [hasError, setHasError] = useState(!initialSrc);
  const [imgSrc, setImgSrc] = useState(initialSrc);

  // Escuchamos si el src cambia desde fuera
  useEffect(() => {
    const newSrc = getStorageUrl(src);
    setImgSrc(newSrc);
    setHasError(!newSrc);
  }, [src]);


  if (hasError) {
    // Si quien llama aporta su propio fallback (p. ej. el placeholder de un ítem), lo usamos en lugar del icono.
    if (fallback) return fallback;
    return (
      <div
        className={`flex items-center justify-center bg-slate-100 text-slate-400 ${className}`}
        {...props}
      >
        <FallbackIcon size={32} strokeWidth={1.5} />
      </div>
    );
  }

  return (
    <img
      src={imgSrc}
      alt={alt}
      className={`object-cover ${className}`}
      onError={(event) => {
        setHasError(true);
        onError?.(event);
      }}
      {...props}
    />
  );
};

export default StorageImage;