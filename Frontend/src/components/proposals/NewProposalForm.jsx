import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Type, AlignLeft, Image as ImageIcon } from 'lucide-react';
import { useCreateProposal } from '../../hooks/proposals/useProposalMutations';
import { useNavigate } from 'react-router-dom';
import { proposalSchema } from '../../lib/schemas';
import { InputField } from '../common/InputField';
import { TextAreaField } from '../common/TextAreaField';

export const NewProposalForm = ({ category }) => {
  const navigate = useNavigate();
  const { mutate: createProposal, isPending } = useCreateProposal();

  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(proposalSchema),
  });

  const onSubmit = (data) => {
    createProposal({
      ...data,
      category_id: category.id
    }, {
      onSuccess: () => navigate('/my-proposals')
    });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
      <InputField
        label="Título / Nombre"
        icon={Type}
        placeholder="Ej: The Legend of Zelda: Ocarina of Time"
        registration={register('name')}
        error={errors.name}
        disabled={isPending}
      />

      <TextAreaField
        label="Descripción detallada"
        icon={AlignLeft}
        rows={6}
        placeholder="Explica por qué merece estar en el juego y qué lo hace especial. Mínimo 20 caracteres."
        registration={register('description')}
        error={errors.description}
        disabled={isPending}
      />

      <InputField
        label="Ruta de Imagen (Opcional)"
        icon={ImageIcon}
        placeholder="Ej: zelda-cover.jpg (solo si está en disco publico)"
        registration={register('image_path')}
        error={errors.image_path}
        disabled={isPending}
      />

      <button
        type="submit"
        disabled={isPending}
        className="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 font-bold text-white shadow-md transition-colors hover:bg-blue-700 disabled:opacity-50"
      >
        {isPending ? <Loader2 size={20} className="animate-spin" /> : 'Enviar a Revisión'}
      </button>
    </form>
  );
};