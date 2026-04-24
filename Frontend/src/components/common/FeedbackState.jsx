import React from 'react';
import clsx from 'clsx';
import {StaggerGrid} from '../animations/StaggerGrid';
import {StaggerItem} from "../animations/StaggerItem.jsx";

export const FeedbackState = ({
                                  icon: Icon,
                                  iconColorClass = 'bg-blue-100 text-blue-500',
                                  title,
                                  description,
                                  actionText,
                                  onAction,
                                  actionColorClass = 'bg-slate-100 text-slate-700 hover:bg-slate-200',
                                  isLoading = false,
                                  customLayoutClass = '',
                                  children
                              }) => {
    return (
        <StaggerGrid className={clsx("flex flex-col items-center text-center", customLayoutClass)}>
            {/* Icono */}
            <StaggerItem>
                {isLoading ? (
                    <Icon className="h-16 w-16 animate-spin text-blue-500 mb-6"/>
                ) : (
                    <div
                        className={clsx("mb-6 flex items-center justify-center relative rounded-full p-4", iconColorClass)}>
                        <Icon size={40} className="relative z-10"/>
                    </div>
                )}
            </StaggerItem>

            {/* Título */}
            {title && (
                <StaggerItem>
                    <h1 className="text-2xl font-black text-slate-900 mb-2">{title}</h1>
                </StaggerItem>
            )}

            {/* Descripción */}
            {description && (
                <StaggerItem>
                    <p className="text-slate-500 mb-6 font-medium max-w-sm text-center">{description}</p>
                </StaggerItem>
            )}

            {/* Children (Botones personalizados como el de MyVotesEmpty) */}
            {children && <StaggerItem className="w-full flex justify-center">{children}</StaggerItem>}

            {/* Botón de acción por defecto */}
            {actionText && onAction && (
                <StaggerItem className="w-full">
                    <button
                        onClick={onAction}
                        className={clsx("w-full rounded-xl py-3 font-bold transition-colors", actionColorClass)}
                    >
                        {actionText}
                    </button>
                </StaggerItem>
            )}
        </StaggerGrid>
    );
};
