import {useState, useCallback} from 'react';

export const useModal = (initialState = false) => {
  const [isOpen, setIsOpen] = useState(initialState);
  const [modalType, setModalType] = useState(null);
  const [modalData, setModalData] = useState(null);

  const openModal = useCallback((type = null, data = null) => {
    setModalType(type);
    setModalData(data);
    setIsOpen(true);
  }, []);

  const closeModal = useCallback(() => {
    setIsOpen(false);
    setTimeout(() => { // Para que no se rompan las animaciones de salida.
      setModalType(null);
      setModalData(null);
    }, 200);
  }, []);

  return {
    isOpen,
    modalType,
    modalData,
    openModal,
    closeModal
  };
};

