import {useState, useCallback} from 'react';

/**
 * Hook reutilizable para gestionar el estado de modales en la aplicación.
 *
 * @param {boolean} initialState - Estado inicial del modal (abierto o cerrado).
 * @returns {object} Objeto con el estado del modal, tipo, datos y funciones para abrir/cerrar.
 * - `isOpen`: Indica si el modal está abierto o cerrado.
 * - `modalType`: Tipo de modal actual (útil para manejar múltiples modales).
 * - `modalData`: Datos asociados al modal (pueden ser usados para mostrar información específica).
 * - `openModal(type, data)`: Función para abrir el modal, opcionalmente con un tipo y datos.
 * - `closeModal()`: Función para cerrar el modal y limpiar su estado después de una animación de salida.
 * */
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

