import React, { useState, useEffect } from 'react';
import { AnimatePresence } from 'framer-motion';
import { ArrowUp } from 'lucide-react';
import { PopButton } from './../animations/PopButton';

export const ScrollToTop = ({ threshold = 800 }) => {
  const [isVisible, setIsVisible] = useState(false);

  useEffect(() => {
    const toggleVisibility = () => {
      if (window.scrollY > threshold) {
        setIsVisible(true);
      } else {
        setIsVisible(false);
      }
    };

    window.addEventListener('scroll', toggleVisibility);

    // Cleanup: evitamos fugas de memoria al desmontar
    return () => window.removeEventListener('scroll', toggleVisibility);
  }, [threshold]);

  const scrollToTop = () => {
    window.scrollTo({
      top: 0,
      behavior: 'smooth',
    });
  };

  return (
    <AnimatePresence>
      {isVisible && (
        <PopButton
          onClick={scrollToTop}
          className="fixed bottom-8 right-8 z-50 flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-yellow-500 hover:-translate-y-1 transition-all"
          aria-label="Volver arriba"
        >
          <ArrowUp size={24} />
        </PopButton>
      )}
    </AnimatePresence>
  );
};