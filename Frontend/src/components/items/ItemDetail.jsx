import React from 'react';

export const ItemDetail = ({ item }) => {
    const STORAGE_URL = "http://localhost:8095/storage/";

    const imageVariant = item.images?.[0]?.variants?.banner || item.images?.[0]?.variants?.thumb;
    const imageUrl = imageVariant ? `${STORAGE_URL}${imageVariant}` : null;

    return (
        <div className="flex flex-col items-center text-center w-full h-[350px] md:h-[380px] xl:h-[550px]">
            <div className="w-full max-w-xl shrink-0 mb-3 md:mb-4 px-2">
                <h2 className="text-xl md:text-2xl xl:text-3xl font-black text-slate-900 leading-tight">
                    {item.name}
                </h2>
            </div>

            {imageUrl ? (
                <div className="w-full max-w-xl aspect-video shrink-0 rounded-2xl mb-3 md:mb-4 shadow-sm bg-slate-50 overflow-hidden border border-slate-200">
                    <img
                        src={imageUrl}
                        alt={item.name}
                        className="w-full h-full object-cover"
                    />
                </div>
            ) : (
                <div className="w-full h-[160px] md:h-[200px] xl:h-[220px] shrink-0 bg-slate-100 rounded-2xl mb-3 md:mb-4 flex items-center justify-center border border-slate-200 shadow-inner">
                    <span className="text-slate-400 font-medium">Sin imagen</span>
                </div>
            )}

            <div className="flex-1 w-full max-w-xl px-2 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200 min-h-0">
                <p className="text-slate-500 text-sm md:text-base leading-relaxed pb-4 text-center">
                    {item.description}
                </p>
            </div>
        </div>
    );
};