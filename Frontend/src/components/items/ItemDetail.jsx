import React from 'react';

export const ItemDetail = ({ item }) => {
  return (
    <div className="flex flex-col items-center text-center w-full h-[350px] md:h-[380px] xl:h-[450px]">
      <div className="w-full max-w-xl shrink-0 mb-3 md:mb-4 px-2">
        <h2 className="text-xl md:text-2xl xl:text-3xl font-black text-slate-900 leading-tight">{item.name}</h2>
      </div>

      {item.images?.[0] ? (
        <div className="w-full h-[160px] md:h-[200px] xl:h-[220px] shrink-0 rounded-2xl mb-3 md:mb-4 shadow-sm bg-slate-50 flex items-center justify-center overflow-hidden border border-slate-200">
           <img
            src={item.images[0]?.path}
            alt={item.name}
            className="w-full h-full object-contain p-2"
          />
        </div>
      ) : (
        <div className="w-full h-[160px] md:h-[200px] xl:h-[220px] shrink-0 bg-slate-100 rounded-2xl mb-3 md:mb-4 flex items-center justify-center border border-slate-200 shadow-inner">
          <span className="text-slate-400 font-medium">Sin imagen</span>
        </div>
      )}

      <div className="flex-1 w-full max-w-xl px-2 overflow-y-auto scrollbar-thin scrollbar-thumb-slate-200 min-h-0">
        <p className="text-slate-500 text-sm md:text-base leading-relaxed pb-4 text-center">{item.description}</p>
      </div>
    </div>
  );
};
