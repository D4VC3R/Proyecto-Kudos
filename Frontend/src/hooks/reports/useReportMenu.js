import { useCallback, useEffect, useId, useLayoutEffect, useRef, useState } from 'react';
import { useCreateReport } from './useReportMutations.js';

// Ancestro más cercano que recorta su contenido (p. ej. la caja de comentarios con scroll); si no hay, el viewport.
const getClippingBounds = (element) => {
  let node = element.parentElement;
  while (node && node !== document.body) {
    const { overflowX, overflowY } = getComputedStyle(node);
    if (/(auto|scroll|hidden)/.test(overflowX + overflowY)) return node.getBoundingClientRect();
    node = node.parentElement;
  }
  return { left: 0, right: window.innerWidth, top: 0, bottom: window.innerHeight };
};

const DEFAULT_PLACEMENT = { alignLeft: false, openUp: false };

// Lógica del menú "⋯" de un contenido y del modal de reporte (F3-04).
export const useReportMenu = ({ type, id }) => {
  const createReport = useCreateReport();

  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [reason, setReason] = useState('');
  const [details, setDetails] = useState('');
  const [error, setError] = useState('');

  const menuId = useId();
  const containerRef = useRef(null);
  const panelRef = useRef(null);
  const [placement, setPlacement] = useState(DEFAULT_PLACEMENT);

  const toggleMenu = useCallback(() => setIsMenuOpen((open) => !open), []);

  const openReport = () => {
    setIsMenuOpen(false);
    setReason('');
    setDetails('');
    setError('');
    setIsModalOpen(true);
  };

  const closeModal = () => setIsModalOpen(false);

  const changeReason = (value) => {
    setReason(value);
    setError('');
  };

  const changeDetails = (value) => {
    setDetails(value);
    setError('');
  };

  const submit = () => {
    if (!reason) {
      setError('Elige un motivo.');
      return;
    }
    if (reason === 'otro' && !details.trim()) {
      setError('Cuéntanos el motivo si eliges «Otro».');
      return;
    }
    createReport.mutate(
      { type, id, reason, details: details.trim() },
      { onSuccess: closeModal }
    );
  };

  // Al abrir, el panel se coloca hacia la derecha del botón y hacia abajo; si así se sale del contenedor
  // que recorta (o de la pantalla), se alinea a la izquierda y/o se abre hacia arriba.
  useLayoutEffect(() => {
    if (!isMenuOpen || !panelRef.current) {
      setPlacement(DEFAULT_PLACEMENT);
      return;
    }
    const panel = panelRef.current.getBoundingClientRect();
    const bounds = getClippingBounds(panelRef.current);
    setPlacement({
      alignLeft: panel.left < bounds.left + 4,
      openUp: panel.bottom > bounds.bottom - 4,
    });
  }, [isMenuOpen]);

  // Mientras el menú está abierto: clic fuera lo cierra; Escape lo cierra y devuelve el foco al botón.
  useEffect(() => {
    if (!isMenuOpen) return undefined;

    const handleMouseDown = (event) => {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setIsMenuOpen(false);
      }
    };
    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        setIsMenuOpen(false);
        // Button no reenvía refs: se busca el botón disparador dentro del contenedor.
        containerRef.current?.querySelector('button')?.focus();
      }
    };

    document.addEventListener('mousedown', handleMouseDown);
    document.addEventListener('keydown', handleKeyDown);
    return () => {
      document.removeEventListener('mousedown', handleMouseDown);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isMenuOpen]);

  return {
    state: {
      isMenuOpen,
      isModalOpen,
      reason,
      details,
      error,
      menuId,
      placement,
      isPending: createReport.isPending,
    },
    actions: { toggleMenu, openReport, closeModal, changeReason, changeDetails, submit },
    refs: { containerRef, panelRef },
  };
};
