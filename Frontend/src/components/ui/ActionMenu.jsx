import React from "react";
import ActionButton from "./ActionButton.jsx";
import { PlayCircle, PlusCircle, Telescope } from 'lucide-react';

const ActionMenu = ({ categorySlug, name, className = "" }) => (
  <nav aria-label={`Acciones de ${name}`} className={className}>
    <ul className="grid grid-cols-1 gap-2 md:grid-cols-3">
      <li>
        <ActionButton
          title="Votar"
          description="¡Tu voto cuenta! Ayuda a decidir el ranking de esta categoría."
          icon={PlayCircle}
          to={`/${categorySlug}/vote`}
        />
      </li>
      <li>
        <ActionButton
          title="Crear Propuesta"
          description="¿Falta tu favorito? Proponlo y gana puntos Kudos si es aceptado."
          icon={PlusCircle}
          to={`/${categorySlug}/proposals/new`}
        />
      </li>
      <li>
        <ActionButton
          title="Explorar"
          description={`Echa un vistazo al catálogo de ${name?.toLowerCase()}, ¡Los tenemos casi todos!`}
          icon={Telescope}
          to={`/${categorySlug}/explore`}
        />
      </li>
    </ul>
  </nav>
);

export default ActionMenu;
