import React from 'react';
import {zodResolver} from '@hookform/resolvers/zod';
import {Type, AlignLeft, Image as ImageIcon} from 'lucide-react';
import {proposalSchema} from '../../lib/schemas';
// Componentes
import InputField from '../ui/InputField.jsx';
import TextAreaField from '../ui/TextAreaField.jsx';
import Button from '../ui/Button.jsx';
// Hooks
import {useCreateProposal, useUpdateProposal} from '../../hooks/proposals/useProposalMutations';
import {useNavigate} from 'react-router-dom';
import {useForm} from 'react-hook-form';

const NewProposalForm = ({category, isEdit = false, initialData = null}) => {
  const navigate = useNavigate();

  const {mutate: createProposal, isPending: isCreating} = useCreateProposal();
  const {mutate: updateProposal, isPending: isUpdating} = useUpdateProposal();

  const isSubmitting = isCreating || isUpdating;

  const defaultValues = isEdit && initialData ? {
    name: initialData.name || '',
    description: initialData.description || '',
    image_path: initialData.images?.[0]?.path || initialData.image_path || '',
  } : {};

  const {register, handleSubmit, formState: {errors}} = useForm({
    resolver: zodResolver(proposalSchema),
    defaultValues,
  });

  const onSubmit = (data) => {
    const payload = {
      ...data,
      image_path: data.image_path?.trim() || '',
      category_id: category.id
    };
    const handleSuccess = () => navigate('/my-proposals');

    if (isEdit) {
      updateProposal(
        {id: initialData.id, data: payload},
        {onSuccess: handleSuccess}
      );
    } else {
      createProposal(payload, {onSuccess: handleSuccess});
    }
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
      <InputField
        label="Título / Nombre"
        icon={Type}
        placeholder="Ej: The Legend of Zelda: Ocarina of Time"
        registration={register('name')}
        error={errors.name}
        disabled={isSubmitting}
      />

      <TextAreaField
        label="Descripción detallada"
        icon={AlignLeft}
        rows={6}
        placeholder="Mínimo 20 caracteres."
        registration={register('description')}
        error={errors.description}
        disabled={isSubmitting}
      />

      <InputField
        label="URL de Imagen (Opcional)"
        icon={ImageIcon}
        type="url"
        placeholder="Ej: https://ejemplo.com/zelda-cover.jpg"
        registration={register('image_path')}
        error={errors.image_path}
        disabled={isSubmitting}
      />

      <Button
        type="submit"
        isLoading={isSubmitting}
        isFullWidth
        variant="solid"
        color="primary"
      >
        {isEdit ? 'Guardar Cambios' : 'Enviar a Revisión'}
      </Button>
    </form>
  );
};

export default NewProposalForm;