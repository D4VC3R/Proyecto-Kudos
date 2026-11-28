import React, { useEffect, useState } from 'react';
import { Reorder, useDragControls } from 'framer-motion';
import { ArrowDown, ArrowUp, GripVertical, Trash2 } from 'lucide-react';
import Button from '../ui/Button.jsx';
import StorageImage from '../ui/StorageImage.jsx';
import Textarea from '../ui/Textarea.jsx';
import ItemPlaceholder from '../ui/ItemPlaceholder.jsx';
import { FOCUS_RING } from '../layout/headerStyles.js';
import { LIST_NOTE_MAX_LENGTH } from '../../lib/lists.js';

// Fila de una entrada en la edición de una lista propia (F3-01). El asa arrastra (framer-motion Reorder);
// los botones ↑/↓ sirven para teclado y móvil. Solo el asa inicia el arrastre, para no bloquear el scroll táctil.
const MyListEntryRow = ({ entry, index, total, isRanked, isBusy, onMove, onRemove, onSaveNote, onDragEnd }) => {
  const controls = useDragControls();
  const savedNote = entry.note ?? '';
  const [note, setNote] = useState(savedNote);
  const thumb = entry.item.images?.[0]?.variants?.thumb;
  const isChanged = note !== savedNote;

  // Si el servidor devuelve otra nota (guardada o recargada), el campo la sigue.
  useEffect(() => {
    setNote(savedNote);
  }, [savedNote]);

  return (
    <Reorder.Item
      as="li"
      value={entry.item.id}
      dragListener={false}
      dragControls={controls}
      onDragEnd={onDragEnd}
      data-list-entry
      className="flex flex-col gap-3 rounded-md border border-line bg-surface px-4 py-3"
    >
      <div className="flex items-center gap-3">
        <button
          type="button"
          onPointerDown={(e) => controls.start(e)}
          aria-label="Arrastrar para reordenar"
          className={`inline-grid h-11 w-8 shrink-0 cursor-grab touch-none place-items-center rounded-sm text-ink-muted hover:bg-line/60 active:cursor-grabbing ${FOCUS_RING}`}
        >
          <GripVertical size={18} aria-hidden="true" />
        </button>
        {isRanked && (
          <span className="w-6 shrink-0 text-center font-display tabular-nums text-accent">{index + 1}</span>
        )}
        <span className="block h-12 w-8 shrink-0 overflow-hidden rounded-sm border border-line bg-surface">
          <StorageImage
            src={thumb}
            alt=""
            width={200}
            height={300}
            className="h-full w-full object-cover"
            fallback={
              <ItemPlaceholder
                name={entry.item.name}
                categorySlug={entry.item.category?.slug}
                categoryIcon={entry.item.category?.icon}
                variant="thumb"
                decorative
                className="h-full w-full"
              />
            }
          />
        </span>
        <div className="min-w-0 flex-1">
          <p className="truncate font-semibold text-ink">{entry.item.name}</p>
          {entry.item.status !== 'active' && <p className="text-caption text-danger">No disponible</p>}
        </div>
        <Button
          onClick={() => onMove(index, -1)}
          variant="ghost"
          icon={ArrowUp}
          className="w-11 shrink-0 px-0"
          aria-label={`Subir «${entry.item.name}»`}
          disabled={index === 0 || isBusy}
        />
        <Button
          onClick={() => onMove(index, 1)}
          variant="ghost"
          icon={ArrowDown}
          className="w-11 shrink-0 px-0"
          aria-label={`Bajar «${entry.item.name}»`}
          disabled={index === total - 1 || isBusy}
        />
        <Button
          onClick={() => onRemove(entry.item.id)}
          variant="ghost"
          icon={Trash2}
          className="w-11 shrink-0 px-0 text-danger"
          aria-label={`Quitar «${entry.item.name}» de la lista`}
          disabled={isBusy}
        />
      </div>

      <Textarea
        label="Nota"
        placeholder="Añade una nota sobre este título"
        rows={2}
        maxLength={LIST_NOTE_MAX_LENGTH}
        value={note}
        onChange={(e) => setNote(e.target.value)}
        labelEnd={<span className="text-caption tabular-nums text-ink-muted">{note.length}/{LIST_NOTE_MAX_LENGTH}</span>}
      />
      {isChanged && (
        <div className="flex justify-end">
          <Button onClick={() => onSaveNote(entry.item.id, note)} variant="secondary" disabled={isBusy}>
            Guardar nota
          </Button>
        </div>
      )}
    </Reorder.Item>
  );
};

export default MyListEntryRow;
