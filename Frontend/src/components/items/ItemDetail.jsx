import React from 'react';

export const ItemDetail = ({ item }) => {
  return (
    <div className="flex flex-col items-center text-center mb-8">
      {item.images?.[0] ? (
        <img 
          src={item.images[0]} 
          alt={item.name} 
          className="w-full h-64 md:h-80 object-cover rounded-2xl mb-6 shadow-md" 
        />
      ) : (
        <div className="w-full h-64 md:h-80 bg-slate-100 rounded-2xl mb-6 flex items-center justify-center border border-slate-200 shadow-inner">
          <span className="text-slate-400 font-medium">Sin imagen</span>
        </div>
      )}
      
      <h2 className="text-3xl font-black text-slate-900 mb-2">{item.name}</h2>
      <p className="text-slate-500 max-w-lg mx-auto">{item.description}</p>
    </div>
  );
};

