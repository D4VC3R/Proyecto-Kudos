import React, { useState, useEffect } from 'react';
import { motion, AnimatePresence, animate } from 'framer-motion';
import { Trophy, Gift, Sparkles, Zap } from 'lucide-react';
import { Modal } from './Modal';
import { Button } from './Button';
import { gamifiedEntryVariants, floatingLoopVariants, pulseLoopVariants, rewardRevealVariants, popInVariants, fadeUpVariants } from '../../lib/animations';

export const DailyRewardModal = ({ isOpen, onClose, data }) => {
  const [isRevealed, setIsRevealed] = useState(false);
  const [displayedKudos, setDisplayedKudos] = useState(0);
  const [isMultiplierActive, setIsMultiplierActive] = useState(false);

  useEffect(() => {
    if (isOpen) {
      setIsRevealed(false);
      setIsMultiplierActive(false);
    }
  }, [isOpen]);

  // Efecto principal para secuenciar el contador de Kudos
  useEffect(() => {
    if (isRevealed && data) {
      if (data.isCritical) {
        setDisplayedKudos(data.baseKudos);

        const timer = setTimeout(() => {
          setIsMultiplierActive(true);

          animate(data.baseKudos, data.kudos, {
            duration: 1.5,
            ease: "circOut", // Efecto de desaceleración (tragaperras)
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

  const isUpgraded = data.isCritical && isMultiplierActive;
  const iconBg = isUpgraded ? 'bg-purple-100 text-purple-600 shadow-purple-200' : 'bg-amber-100 text-amber-500 shadow-amber-200';
  const textColor = isUpgraded ? 'text-purple-600' : 'text-blue-600';

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
            <motion.div
              key="mystery-box"
              variants={gamifiedEntryVariants}
              initial="hidden"
              animate="visible"
              exit="exit"
              className="flex flex-col items-center cursor-pointer group"
              onClick={() => setIsRevealed(true)}
            >
              <motion.div variants={floatingLoopVariants} animate="animate" className="relative bg-gradient-to-br from-indigo-500 to-purple-600 p-6 sm:p-8 rounded-full shadow-lg shadow-purple-500/30 mb-4 group-hover:scale-105 transition-transform">
                <Gift size={56} className="text-white" strokeWidth={1.5} />
                <motion.div variants={pulseLoopVariants} animate="animate" className="absolute -top-1 -right-1 text-amber-300">
                  <Sparkles size={24} />
                </motion.div>
              </motion.div>
              <h2 className="text-xl sm:text-2xl font-bold text-slate-800">¡Tu bonus diario está listo!</h2>
              <p className="text-sm sm:text-base text-slate-500 mt-2 animate-pulse">Toca para abrir</p>
            </motion.div>
          ) : (
            <motion.div
              key="reward-content"
              variants={rewardRevealVariants}
              initial="hidden"
              animate="visible"
              className="flex flex-col items-center w-full"
            >

              <motion.div
                layout
                className={`flex items-center justify-center h-20 w-20 sm:h-24 sm:w-24 rounded-full mb-4 shadow-inner transition-colors duration-700 ${iconBg}`}
              >
                <Trophy size={48} strokeWidth={2} />
              </motion.div>

              <motion.h2 variants={popInVariants} className="text-3xl sm:text-5xl font-extrabold text-slate-800 tracking-tight">
                ¡Día {data.streak}!
              </motion.h2>

              <motion.div variants={fadeUpVariants} className="mt-3 flex flex-col items-center">
                <p className="text-base sm:text-lg text-slate-600">
                  Has ganado
                </p>
                <span className={`font-black text-4xl sm:text-5xl transition-colors duration-700 ${textColor}`}>
                  +{displayedKudos}
                </span>
                <p className="text-sm text-slate-500 font-semibold mt-1">Kudos</p>
              </motion.div>

              <AnimatePresence>
                {isUpgraded && (
                  <motion.div
                    initial={{ scale: 0, opacity: 0, y: 20 }}
                    animate={{ scale: 1, opacity: 1, y: 0 }}
                    transition={{ type: "spring", stiffness: 300, damping: 15 }}
                    className="flex items-center gap-2 text-purple-700 font-black mt-4 bg-purple-100 px-4 py-2 rounded-full border border-purple-300 shadow-md"
                  >
                    <Zap size={18} className="animate-pulse text-purple-600" fill="currentColor" />
                    <span className="text-sm uppercase tracking-wider">¡Multiplicador x{data.multiplier}!</span>
                  </motion.div>
                )}
              </AnimatePresence>

              {data.isNewRecord && (
                <motion.div variants={fadeUpVariants} className="flex items-center gap-2 bg-gradient-to-r from-amber-100 to-amber-50 text-amber-800 px-4 py-2 sm:px-6 sm:py-3 rounded-full font-bold mt-5 shadow-sm border border-amber-200">
                  <Sparkles size={18} className="text-amber-500 animate-pulse" />
                  <span className="text-sm sm:text-base">¡Nuevo Récord Histórico!</span>
                </motion.div>
              )}
            </motion.div>
          )}
        </AnimatePresence>

      </div>
    </Modal>
  );
};