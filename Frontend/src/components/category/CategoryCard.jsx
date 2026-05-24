import React from 'react';
import {Link} from 'react-router-dom';
import {BouncyCard} from '../animations/BouncyCard';
import {HoverFadeText} from '../animations/HoverFadeText';
import {getCategoryIcon} from "../../lib/constants.js";
import StorageImage from "../common/StorageImage.jsx";

export const CategoryCard = ({category}) => {

  const Icon = getCategoryIcon(category.name);


  return (
    <Link to={`/${category.slug}`}>
      <BouncyCard
        className="group relative h-80 w-full cursor-pointer overflow-hidden rounded-3xl bg-white shadow-lg ring-1 ring-slate-200 transition-shadow hover:shadow-2xl hover:shadow-blue-500/20">
        <div className="absolute inset-0">
          <div className="absolute inset-0">
            <StorageImage
              src={category?.image}
              alt={category.name}
              className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
            />
            <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/40 to-transparent" />
          </div>
          <div className="absolute inset-0 bg-gradient-to-t from-slate-900 via-slate-900/60 to-transparent"/>
        </div>

        <div
          className="absolute inset-0 -translate-x-full bg-gradient-to-r from-transparent via-white/30 to-transparent skew-x-12 transition-transform duration-700 ease-out group-hover:translate-x-full"/>

        <div className="absolute inset-0 flex flex-col justify-end p-6 hover:border-4 border-blue-600 rounded-3xl">
          <div className="absolute right-4 top-4">
            <div
              className="flex items-center gap-1.5 rounded-full bg-yellow-400 px-3 py-1.5 font-bold text-yellow-950 shadow-md">
              <Icon size={16} className="fill-yellow-600 stroke-yellow-700"/>
              <span className="text-sm">{category.items_count || 0}</span>
            </div>
          </div>

          <HoverFadeText className="transform transition-all">
            <h2 className="mb-2 font-black text-3xl text-white tracking-tight drop-shadow-md">
              {category.name}
            </h2>
            <p className="line-clamp-2 text-sm text-slate-200 font-medium leading-relaxed">
              {category.description}
            </p>
          </HoverFadeText>
        </div>
      </BouncyCard>
    </Link>
  );
};