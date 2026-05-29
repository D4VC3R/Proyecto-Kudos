import React from 'react';
import { Search } from 'lucide-react';

const SearchFilter = ({value, onChange, placeholder = "Buscar...", maxWidth = "max-w-[200px]"}) => {
  return (
    <div className="relative">
      <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={18} />
      <input
        type="text"
        placeholder={placeholder}
        onChange={onChange}
        value={value}
        className={`w-full ${maxWidth} rounded-2xl border-border bg-background pl-10 py-2.5 text-sm focus:bg-surface focus:ring-2 focus:ring-blue-500 font-medium`}
      />
    </div>
  );
};

export default SearchFilter;