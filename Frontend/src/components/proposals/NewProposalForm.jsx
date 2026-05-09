import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Type, AlignLeft, Image as ImageIcon } from 'lucide-react';
import { useCreateProposal } from '../../hooks/proposals/useProposalMutations';
import { useNavigate } from 'react-router-dom';
import { proposalSchema } from '../../lib/schemas';
import { InputField } from '../common/InputField';
import { TextAreaField } from '../common/TextAreaField';
import { Button } from '../common/Button';

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
      onSuccess: () => navigate('/my-proposals'),
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
        placeholder="Mínimo 20 caracteres."
        registration={register('description')}
        error={errors.description}
        disabled={isPending}
      />

      <InputField
        label="Ruta de Imagen (Opcional)"
        icon={ImageIcon}
        placeholder="Ej: zelda-cover.jpg"
        registration={register('image_path')}
        error={errors.image_path}
        disabled={isPending}
      />

      <Button
        type="submit"
        isLoading={isPending}
        isFullWidth
        variant="solid"
        color="primary"
      >
        Enviar a Revisión
      </Button>
    </form>
  );
};