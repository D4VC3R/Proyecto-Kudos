import React from 'react';
import clsx from 'clsx';
import {StaggerGrid} from '../animations/StaggerGrid';
import {StaggerItem} from "../animations/StaggerItem.jsx";
import { Button } from './Button';

export const FeedbackState = ({
                                  icon: Icon,
                                  iconColorClass = 'bg-blue-100 text-blue-500',
                                  title,
                                  description,
                                  actionText,
                                  onAction,
                                  isLoading = false,
                                  customLayoutClass = '',
                                  children
                              }) => {
    return (
        <StaggerGrid className={clsx("flex flex-col items-center text-center", customLayoutClass)}>
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

            {title && (
                <StaggerItem>
                    <h1 className="text-2xl font-black text-slate-900 mb-2">{title}</h1>
                </StaggerItem>
            )}

            {description && (
                <StaggerItem>
                    <p className="text-slate-500 mb-6 font-medium max-w-sm text-center">{description}</p>
                </StaggerItem>
            )}

            {children && <StaggerItem className="w-full flex justify-center">{children}</StaggerItem>}

            {actionText && onAction && (
                <StaggerItem className="w-full">
                    <Button
                        onClick={onAction}
                        isFullWidth
                        variant="solid"
                        color="neutral"
                    >
                        {actionText}
                    </Button>
                </StaggerItem>
            )}
        </StaggerGrid>
    );
};
