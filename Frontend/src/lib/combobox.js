// Lógica pura del patrón combobox de ARIA (buscador global, F0-20). Sin React, para probarla con node.

// Índice cuando no hay ninguna opción activa.
export const NO_ACTIVE_OPTION = -1;

// Opción activa tras pulsar `key` con `optionCount` opciones.
// ↓ desde ninguna va a la primera y ↑ a la última; en los extremos da la vuelta.
// Con otra tecla devuelve el índice actual; un índice fuera de rango cuenta como "ninguna".
export const getNextActiveIndex = (key, activeIndex, optionCount) => {
  if (!Number.isInteger(optionCount) || optionCount <= 0) return NO_ACTIVE_OPTION;
  const current = Number.isInteger(activeIndex) && activeIndex >= 0 && activeIndex < optionCount
    ? activeIndex
    : NO_ACTIVE_OPTION;
  if (key === 'ArrowDown') return current === NO_ACTIVE_OPTION ? 0 : (current + 1) % optionCount;
  if (key === 'ArrowUp') return current === NO_ACTIVE_OPTION ? optionCount - 1 : (current - 1 + optionCount) % optionCount;
  return current;
};

// Opción activa, o null si no hay ninguna (entonces Enter va a /buscar).
export const getActiveOption = (options, activeIndex) => {
  if (!Array.isArray(options) || !Number.isInteger(activeIndex) || activeIndex < 0) return null;
  return options[activeIndex] ?? null;
};

// id de la opción `index` del listbox, para aria-activedescendant.
export const getOptionId = (listboxId, index) => `${listboxId}-option-${index}`;
