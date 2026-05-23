import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Save, MapPin, Calendar, Image as ImageIcon } from 'lucide-react';
import { useUpdateProfile } from '../../hooks/users/useUserMutations';
import { profileSchema } from '../../lib/schemas';
import { InputField } from '../common/InputField';
import { TextAreaField } from '../common/TextAreaField';
import { Button } from '../common/Button';

export const ProfileEditForm = ({ profile }) => {
  const { mutate: updateProfile, isPending } = useUpdateProfile();

  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      avatar: '',
      biography: profile?.biography || '',
      city: profile?.city || '',
      birthdate: profile?.birthdate || '',
    }
  });

  const onSubmit = (data) => {
    const payload = {
      ...data,
      avatar: data.avatar || null,
      biography: data.biography || null,
      city: data.city || null,
      birthdate: data.birthdate || null,
    };
    updateProfile(payload);
  };

  return (
    <div className="flex-1 bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200">
      <h3 className="text-xl font-black text-slate-900 mb-6 border-b border-slate-100 pb-4">
        Editar Perfil
      </h3>

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
        <InputField
          label="URL del Avatar"
          type="url"
          icon={ImageIcon}
          placeholder="https://ejemplo.com/avatar.jpg"
          registration={register('avatar')}
          error={errors.avatar}
          disabled={isPending}
        />

        <TextAreaField
          label="Biografía"
          rows={4}
          placeholder="Cuéntanos un poco sobre ti..."
          registration={register('biography')}
          error={errors.biography}
          disabled={isPending}
        />

        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          <InputField
            label="Ciudad"
            type="text"
            icon={MapPin}
            placeholder="Ej. Madrid"
            registration={register('city')}
            error={errors.city}
            disabled={isPending}
          />

          <InputField
            label="Fecha de Nacimiento"
            type="date"
            icon={Calendar}
            registration={register('birthdate')}
            error={errors.birthdate}
            disabled={isPending}
          />
        </div>

        <div className="flex justify-end mt-4 pt-4 border-t border-slate-100">
          <Button
            type="submit"
            isLoading={isPending}
            variant="solid"
            color="primary"
            icon={Save}
          >
            Guardar Cambios
          </Button>
        </div>
      </form>
    </div>
  );
};