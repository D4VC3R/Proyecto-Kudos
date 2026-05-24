import React, { useState, useEffect } from 'react';
import { AnimatePresence, animate } from 'framer-motion';
import { Modal } from './Modal';
import { Button } from './Button';
import { MysteryBox } from './../animations/MysteryBox.jsx';
import { RewardReveal } from './../animations/RewardReveal';

export const DailyRewardModal = ({ isOpen, onClose, data }) => {
  const [isRevealed, setIsRevealed] = useState(false);
  const [displayedKudos, setDisplayedKudos] = useState(0);
  const [isMultiplierActive, setIsMultiplierActive] = useState(false);

  // Reiniciar estado al abrir el modal
  useEffect(() => {
    if (isOpen) {
      setIsRevealed(false);
      setIsMultiplierActive(false);
    }
  }, [isOpen]);

  // Orquestador de la animación numérica
  useEffect(() => {
    if (isRevealed && data) {
      if (data.isCritical) {
        setDisplayedKudos(data.baseKudos);

        const timer = setTimeout(() => {
          setIsMultiplierActive(true);
          animate(data.baseKudos, data.kudos, {
            duration: 1.5,
            ease: "circOut",
            onUpdate: (latest) => setDisplayedKudos(Math.round(latest))
          });
        }, 800);

        return () => clearTimeout(timer);
      } else {
        setDisplayedKudos(data.kudos);
      }
    }
  }, [isRevealed, data]);

  if (!data) return null;

  const handleClose = () => {
    setIsRevealed(false);
    onClose();
  };

  const modalFooter = isRevealed ? (
    <Button onClick={handleClose} variant="solid" color="primary" isFullWidth>
      ¡Recoger Recompensa!
    </Button>
  ) : null;

  return (
    <Modal
      isOpen={isOpen}
      onClose={handleClose}
      title={isRevealed ? "¡Recompensa Desbloqueada!" : "Recompensa Diaria"}
      footer={modalFooter}
    >
      <div className="relative flex flex-col items-center justify-center min-h-[260px] sm:min-h-[300px] text-center px-2 py-4">

        <AnimatePresence mode="wait">
          {!isRevealed ? (
            <MysteryBox key="mystery-box" onReveal={() => setIsRevealed(true)} />
          ) : (
            <RewardReveal
              key="reward-content"
              data={data}
              displayedKudos={displayedKudos}
              isMultiplierActive={isMultiplierActive}
            />
          )}
        </AnimatePresence>

      </div>
    </Modal>
  );
};