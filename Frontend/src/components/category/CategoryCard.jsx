import React from 'react';
import { motion } from 'framer-motion';
import { Gamepad2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import {bouncyCardVariants} from "../../lib/animations.js";

export const CategoryCard = ({ category }) => {
    return (
        <Link to={`/categories/${category.id}`}>
            <motion.article
                variants={bouncyCardVariants} // <-- Lo usamos aquí
                whileHover="hover"
                whileTap="tap"
                className="group relative h-80 w-full cursor-pointer overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-slate-200 transition-shadow hover:shadow-2xl hover:shadow-blue-500/20"
            >
                {/* Imagen de fondo con overlay degradado */}
                <div className="absolute inset-0">
                    <img
                        src={category.image || 'https://via.placeholder.com/400x300?text=Kudos+Arena'}
                        alt={category.name}
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                    />
                    {/* Gradiente que oscurece la parte inferior para que el texto sea legible */}
                    <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent" />
                </div>

                {/* Efecto de Brillo (Shine) que cruza la tarjeta en Hover */}
                <div className="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/30 to-transparent skew-x-12 transition-transform duration-700 ease-out group-hover:translate-x-full" />

                {/* Contenido (Posicionado abajo) */}
                <div className="absolute inset-0 flex flex-col justify-end p-6">
                    {/* Badge flotante de cantidad de items */}
                    <div className="absolute right-4 top-4">
                        <div className="flex items-center gap-1.5 rounded-full bg-yellow-400 px-3 py-1.5 font-bold text-yellow-950 shadow-md">
                            <Gamepad2 size={16} className="fill-yellow-600 stroke-yellow-700" />
                            <span className="text-sm">{category.items_count || 0}</span>
                        </div>
                    </div>

                    <motion.div
                        initial={{ y: 10, opacity: 0.9 }}
                        whileHover={{ y: 0, opacity: 1 }}
                        className="transform transition-all"
                    >
                        <h2 className="mb-2 font-black text-3xl text-white tracking-tight drop-shadow-md">
                            {category.name}
                        </h2>
                        <p className="line-clamp-2 text-sm text-slate-200 font-medium leading-relaxed">
                            {category.description}
                        </p>
                    </motion.div>
                </div>
            </motion.article>
        </Link>
    );
};