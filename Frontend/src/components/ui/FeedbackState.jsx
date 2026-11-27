import React from 'react';
import clsx from 'clsx';
import StaggerGrid from '../animations/StaggerGrid.jsx';
import StaggerItem from "../animations/StaggerItem.jsx";
import Button from './Button.jsx';

const FeedbackState = ({
                         icon: Icon,
                         iconColorClass = 'bg-accent/10 text-accent',
                         title,
                         titleAs: Title = 'h1',
                         description,
                         actionText,
                         onAction,
                         isLoading = false,
                         customLayoutClass = '',
                         children
                       }) => {
  return (
    <StaggerGrid
      role={isLoading ? 'status' : undefined}
      className={clsx("flex flex-col items-center text-center", customLayoutClass)}
    >
      <StaggerItem>
        {isLoading ? (
          <Icon aria-hidden="true" className="h-16 w-16 animate-spin text-accent mb-6"/>
        ) : (
          <div
            className={clsx("mb-6 flex items-center justify-center relative rounded-full p-4", iconColorClass)}>
            <Icon aria-hidden="true" size={40} className="relative z-10"/>
          </div>
        )}
      </StaggerItem>

      {title && (
        <StaggerItem>
          <Title className="mb-2 font-display text-h2 text-ink">{title}</Title>
        </StaggerItem>
      )}

      {description && (
        <StaggerItem>
          <p className="text-ink-muted mb-6 font-medium max-w-sm text-center">{description}</p>
        </StaggerItem>
      )}

      {children && <StaggerItem className="w-full flex justify-center">{children}</StaggerItem>}

      {actionText && onAction && (
        <StaggerItem className="w-full">
          <Button
            onClick={onAction}
            isFullWidth
            variant="secondary"
          >
            {actionText}
          </Button>
        </StaggerItem>
      )}
    </StaggerGrid>
  );
};

export default FeedbackState;