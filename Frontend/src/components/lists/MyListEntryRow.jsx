import React, { useEffect, useState } from 'react';
import { Reorder, useDragControls } from 'framer-motion';
import { ArrowDown, ArrowUp, GripVertical, Trash2 } from 'lucide-react';
import Button from '../ui/Button.jsx';
import StorageImage from '../ui/StorageImage.jsx';
import TextAreaField from '../ui/TextAreaField.jsx';
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
      className="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-surface px-4 py-3"
    >
      <div className="flex items-center gap-3">
        <button
          type="button"
          onPointerDown={(e) => controls.start(e)}
          aria-label="Arrastrar para reordenar"
          className="shrink-0 cursor-grab touch-none rounded-lg p-1 text-slate-400 hover:bg-slate-100 active:cursor-grabbing"
        >
          <GripVertical size={18} />
        </button>
        {isRanked && <span className="w-6 shrink-0 text-center font-black text-primary">{index + 1}</span>}
        {thumb && <StorageImage src={thumb} alt="" className="h-12 w-9 shrink-0 rounded-lg object-cover" />}
        <div className="min-w-0 flex-1">
          <p className="truncate font-bold text-slate-800">{entry.item.name}</p>
          {entry.item.status !== 'active' && <p className="text-xs font-bold text-red-500">No disponible</p>}
        </div>
        <Button
          onClick={() => onMove(index, -1)}
          variant="ghost"
          color="neutral"
          size="iconMd"
          icon={ArrowUp}
          aria-label="Subir"
          disabled={index === 0 || isBusy}
        />
        <Button
          onClick={() => onMove(index, 1)}
          variant="ghost"
          color="neutral"
          size="iconMd"
          icon={ArrowDown}
          aria-label="Bajar"
          disabled={index === total - 1 || isBusy}
        />
        <Button
          onClick={() => onRemove(entry.item.id)}
          variant="ghost"
          color="danger"
          size="iconMd"
          icon={Trash2}
          aria-label="Quitar de la lista"
          disabled={isBusy}
        />
      </div>

      <TextAreaField
        label="Nota"
        placeholder="Añade una nota sobre este título"
        rows={2}
        maxLength={LIST_NOTE_MAX_LENGTH}
        value={note}
        onChange={(e) => setNote(e.target.value)}
        labelEnd={<span className="text-xs font-bold text-slate-400">{note.length}/{LIST_NOTE_MAX_LENGTH}</span>}
      />
      {isChanged && (
        <div className="flex justify-end">
          <Button onClick={() => onSaveNote(entry.item.id, note)} size="sm" disabled={isBusy}>
            Guardar nota
          </Button>
        </div>
      )}
    </Reorder.Item>
  );
};

export default MyListEntryRow;
