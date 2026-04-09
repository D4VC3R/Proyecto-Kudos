import React, {useState, useEffect} from 'react';
import {Search} from 'lucide-react';

export const TableSearch = ({
                                initialValue = '',
                                onSearch,
                                placeholder = 'Buscar...'
                            }) => {
    const [searchTerm, setSearchTerm] = useState(initialValue);

    // Retrasa la ejecución de la búsqueda
    useEffect(() => {
        const delayDebounceFn = setTimeout(() => {
            // Solo buscamos si el término ha cambiado realmente respecto al estado global
            onSearch(searchTerm);
        }, 500); // 500ms de espera desde la última pulsación

        return () => clearTimeout(delayDebounceFn);
    }, [searchTerm, onSearch]);

    
    return (
        <div className="relative flex max-w-sm w-full items-center">
            <div className="absolute left-3 text-slate-400 pointer-events-none">
                <Search size={18}/>
            </div>
            <input
                type="text"
                className="w-full rounded-lg border border-slate-700 bg-slate-900 py-2 pl-10 pr-4 text-sm text-slate-200 placeholder-slate-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                placeholder={placeholder}
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
            />
        </div>
    );
};