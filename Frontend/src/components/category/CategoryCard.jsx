import React from 'react';
import { Link } from 'react-router-dom';
import { getCategoryIcon } from "../../lib/constants.js";
import BouncyCard from '../animations/BouncyCard';
import HoverFadeText from '../animations/HoverFadeText';
import StorageImage from "../common/StorageImage.jsx";

const CategoryCard = ({ category }) => {
  const Icon = getCategoryIcon(category.name);

  return (
    <Link to={`/${category.slug}`}>
      <BouncyCard
        className="group relative h-80 w-full cursor-pointer overflow-hidden rounded-3xl bg-slate-100 shadow-md transition-shadow duration-300 hover:shadow-[0_20px_50px_-12px_rgba(37,99,235,0.6)]"
      >
        <div className="absolute inset-0">
          <StorageImage
            src={category?.image}
            alt={category.name}
            className="h-full w-full object-cover transition-transform duration-700 ease-out group-hover:scale-110"
          />
          <div className="absolute inset-0 bg-gradient-to-t from-slate-900/95 via-slate-900/40 to-transparent/10" />
        </div>

        <div className="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/20 to-transparent skew-x-12 transition-transform duration-700 ease-out group-hover:translate-x-full" />
        <div className="absolute inset-0 flex flex-col justify-end p-6 rounded-3xl transition-all duration-300 group-hover:ring-4 group-hover:ring-inset group-hover:ring-blue-500">
          <div className="absolute right-4 top-4">
            <div className="flex items-center gap-1.5 rounded-full bg-accent px-3 py-1.5 font-bold text-yellow-950 shadow-md">
              <Icon size={16} className="fill-yellow-600 stroke-yellow-700" />
              <span className="text-sm">{category.items_count || 0}</span>
            </div>
          </div>

          <HoverFadeText className="transform transition-all">
            <h2 className="mb-2 font-black text-3xl text-text-btn tracking-tight drop-shadow-md">
              {category.name}
            </h2>
            <p className="line-clamp-2 text-sm text-text-subtitle font-medium leading-relaxed">
              {category.description}
            </p>
          </HoverFadeText>
        </div>
      </BouncyCard>
    </Link>
  );
};

export default CategoryCard;