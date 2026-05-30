import React from "react";
import StaggerGrid from "../animations/StaggerGrid.jsx";
import StaggerItem from "../animations/StaggerItem.jsx";
import ActionButton from "./ActionButton.jsx";
import {BookOpenCheckIcon, PlayCircle, PlusCircle, SquareSplitHorizontalIcon, Telescope} from 'lucide-react';


const ActionMenu = ({categorySlug, name}) => {
    return (
        <div className="flex flex-col gap-6 lg:col-span-5 items-center">
            <StaggerGrid className="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-1 gap-3 sm:gap-4 lg:gap-5 rounded-3xl bg-surface p-4 sm:p-5 lg:p-8 shadow-xl ring-1 ring-slate-200">
                <StaggerItem>
                    <ActionButton
                        title="Votar"
                        description="¡Tu voto cuenta! Ayuda a decidir el ranking de esta categoría."
                        icon={PlayCircle}
                        to={`/${categorySlug}/vote`}
                        color="blue"
                    />
                </StaggerItem>

                <StaggerItem>
                    <ActionButton
                        title="Crear Propuesta"
                        description="¿Falta tu favorito? Proponlo y gana puntos."
                        icon={PlusCircle}
                        to={`/${categorySlug}/proposals/new`}
                        color="red"
                    />
                </StaggerItem>

                <StaggerItem>
                    <ActionButton
                        title="Explorar"
                        description={`Echa un vistazo al catálogo de ${name}.`}
                        icon={Telescope}
                        to={`/${categorySlug}/explore`}
                        color="green"
                    />
                </StaggerItem>
                <StaggerItem>
                    <ActionButton
                        title="Enfrentamiento (En desarrollo...)"
                        description="Elige tu favorito entre dos opciones al azar."
                        icon={SquareSplitHorizontalIcon}
                        color="yellow"
                    />
                </StaggerItem>
                <StaggerItem>
                    <ActionButton
                        title="Trivia (En desarrollo...)"
                        description="Pon a prueba tus conocimientos."
                        icon={BookOpenCheckIcon}
                        color="purple"
                    />
                </StaggerItem>
            </StaggerGrid>
        </div>

    );
}

export default ActionMenu;