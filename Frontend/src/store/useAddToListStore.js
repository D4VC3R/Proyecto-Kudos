import { create } from 'zustand';

// Ítem para el que está abierto el modal "Añadir a una lista" (F3-01). Sin persistencia.
// El modal se monta una sola vez en App.jsx y se abre desde las tarjetas y las fichas.
export const useAddToListStore = create((set) => ({
  item: null,
  openAddToList: (item) => set({ item }),
  closeAddToList: () => set({ item: null }),
}));
