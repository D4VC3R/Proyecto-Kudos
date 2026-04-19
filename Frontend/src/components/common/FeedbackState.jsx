import React from 'react';
import clsx from 'clsx';
import { motion } from 'framer-motion';
export const FeedbackState = ({
  icon: Icon,
  iconColorClass = 'bg-blue-100 text-blue-500',
  title,
  description,
  actionText,
  onAction,
  actionColorClass = 'bg-slate-100 text-slate-700 hover:bg-slate-200',
  isAnimated = false,
  isLoading = false,
  customLayoutClass = '',
  children
}) => {
  const content = (
    <>
      {isLoading ? (
         <Icon className="h-16 w-16 animate-spin text-blue-500 mb-6" />
      ) : (
        <div className={clsx("mb-6 flex items-center justify-center relative", iconColorClass)}>
          <Icon size={40} className="relative z-10" />
        </div>
      )}
      {title && <h1 className="text-2xl font-black text-slate-900 mb-2">{title}</h1>}
      {description && <p className="text-slate-500 mb-6 font-medium max-w-sm text-center">{description}</p>}
      {children}
      {actionText && onAction && (
        <button
          onClick={onAction}
          className={clsx("w-full rounded-xl py-3 font-bold transition-colors", actionColorClass)}
        >
          {actionText}
        </button>
      )}
    </>
  );
  if (isAnimated) {
    return (
      <motion.div
        initial={{ opacity: 0, scale: 0.95 }}
        animate={{ opacity: 1, scale: 1 }}
        className={clsx("flex flex-col items-center justify-center p-12 text-center", customLayoutClass)}
      >
         {content}
      </motion.div>
    );
  }
  return (
    <div className={clsx("flex flex-col items-center text-center", customLayoutClass)}>
       {content}
    </div>
  );
};
