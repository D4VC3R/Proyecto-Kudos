import React from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Type, AlignLeft, Image as ImageIcon, Database, Plus, Trash2 } from 'lucide-react';
import { useCreateProposal } from '../../hooks/proposals/useProposalMutations';
import { useNavigate } from 'react-router-dom';
import { proposalSchema } from '../../lib/schemas/proposalSchema';
import { formatKeyName } from '../../lib/utils/formatters';
import { buildProposalPayload } from '../../lib/utils/proposalUtils';

export const NewProposalForm = ({ category }) => {
  const navigate = useNavigate();

  const { mutate: createProposal, isPending } = useCreateProposal();

  const defaultExtraFields = [];
  if (category.field_definitions && category.field_definitions.length > 0) {
    category.field_definitions.forEach(def => {
      defaultExtraFields.push({
        key: def.key,
        label: def.label || formatKeyName(def.key),
        value: '',
        required: def.required,
        readonlyKey: true
      });
    });
  }

  const { register, control, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(proposalSchema),
    defaultValues: {
      extra_fields: defaultExtraFields
    }
  });

  const { fields, append, remove } = useFieldArray({
    control,
    name: 'extra_fields'
  });

  const onSubmit = (data) => {
    const payload = buildProposalPayload(data, category.id);

    createProposal(payload, {
      onSuccess: () => navigate('/my-proposals')
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
      <div>
        <label className="mb-2 block text-sm font-bold text-slate-700">Título / Nombre</label>
        <div className="relative">
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <Type size={18} />
          </div>
          <input
            type="text"
            {...register('name')}
            disabled={isPending}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
            placeholder="Ej: The Legend of Zelda: Ocarina of Time"
          />
        </div>
        {errors.name && <span className="mt-1 text-xs text-red-500">{errors.name.message}</span>}
      </div>

      <div>
        <label className="mb-2 block text-sm font-bold text-slate-700">Descripción detallada</label>
        <div className="relative">
          <div className="pointer-events-none absolute top-3 left-0 flex pl-3 text-slate-400">
            <AlignLeft size={18} />
          </div>
          <textarea
            {...register('description')}
            disabled={isPending}
            rows={6}
            className="w-full resize-none rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
            placeholder="Explica por qué merece estar en el juego y qué lo hace especial. Mínimo 20 caracteres."
          />
        </div>
        {errors.description && <span className="mt-1 text-xs text-red-500">{errors.description.message}</span>}
      </div>

      <div>
        <label className="mb-2 block text-sm font-bold text-slate-700">Ruta de Imagen (Opcional)</label>
        <div className="relative">
          <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <ImageIcon size={18} />
          </div>
          <input
            type="text"
            {...register('image_path')}
            disabled={isPending}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
            placeholder="Ej: zelda-cover.jpg (solo si está en disco publico)"
          />
        </div>
      </div>

      <div>
        <div className="mb-3 flex items-center justify-between">
          <label className="block text-sm font-bold text-slate-700">Campos Extra para {category.name}</label>
          <button
            type="button"
            onClick={() => {
              if (fields.length < 10) append({ key: '', value: '', required: false, readonlyKey: false });
            }}
            disabled={isPending || fields.length >= 10}
            className="flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-800 disabled:opacity-50"
          >
            <Plus size={16} /> Agregar campo extra
          </button>
        </div>

        {fields.length > 0 && (
          <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            {fields.map((item, index) => (
              <div key={item.id} className="flex gap-2 items-start">
                <div className="flex-1">
                  {item.readonlyKey ? (
                    <>
                      <input type="hidden" {...register(`extra_fields.${index}.key`)} />
                      <div className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 font-bold bg-slate-200/70 truncate flex items-center min-h-[38px]">
                        {item.label || item.key}
                      </div>
                    </>
                  ) : (
                    <input
                      {...register(`extra_fields.${index}.key`)}
                      disabled={isPending}
                      placeholder="Nombre (ej: Género)"
                      className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 min-h-[38px]"
                    />
                  )}
                  {errors.extra_fields?.[index]?.key && (
                    <span className="mt-1 block text-xs text-red-500">{errors.extra_fields[index].key.message}</span>
                  )}
                  {item.required && (
                    <span className="text-[10px] text-red-500 font-bold ml-1">Requerido por la categoría</span>
                  )}
                </div>
                <div className="flex-1">
                  <input
                    {...register(`extra_fields.${index}.value`)}
                    disabled={isPending}
                    placeholder="Valor (ej: Drama)"
                    className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                  />
                  {errors.extra_fields?.[index]?.value && (
                    <span className="mt-1 block text-xs text-red-500">{errors.extra_fields[index].value.message}</span>
                  )}
                </div>
                {!item.readonlyKey ? (
                  <button
                    type="button"
                    onClick={() => remove(index)}
                    disabled={isPending}
                    className="p-2 text-slate-400 hover:text-red-500 transition-colors"
                    title="Eliminar campo"
                  >
                    <Trash2 size={18} />
                  </button>
                ) : (
                  <div className="w-[34px]" />
                )}
              </div>
            ))}
            <p className="text-xs text-slate-500 mt-1">Has agregado {fields.length} campos en total.</p>
          </div>
        )}
        {fields.length === 0 && (
          <div className="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 text-center">
            <Database size={24} className="mx-auto mb-2 text-slate-400" />
            <p className="text-sm text-slate-500 font-medium">Añade propiedades extra si tu ítem lo requiere.</p>
          </div>
        )}
      </div>

      <button
        type="submit"
        disabled={isPending}
        className="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 font-bold text-white transition-colors hover:bg-blue-700 disabled:opacity-50 shadow-md"
      >
        {isPending ? <Loader2 size={20} className="animate-spin" /> : 'Enviar a Revisión'}
      </button>
    </form>
  );
};
