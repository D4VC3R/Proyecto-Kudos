import React from 'react';
import {zodResolver} from '@hookform/resolvers/zod';
import {Link} from 'react-router-dom';
import {Save, MapPin, Calendar, AtSign, Image as ImageIcon} from 'lucide-react';
import {profileSchema} from '../../lib/schemas';
//  Componentes
import InputField from '../ui/InputField.jsx';
import TextAreaField from '../ui/TextAreaField.jsx';
import Button from '../ui/Button.jsx';
// Hooks
import {useForm} from 'react-hook-form';
import {useUpdateProfile} from '../../hooks/users/useUserMutations';

const ProfileEditForm = ({profile}) => {
  const {mutate: updateProfile, isPending} = useUpdateProfile();

  const {register, handleSubmit, formState: {errors}} = useForm({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      avatar: '',
      biography: profile?.biography || '',
      city: profile?.city || '',
      birthdate: profile?.birthdate || '',
      username: profile?.username || '',
      is_public: Boolean(profile?.is_public),
    }
  });

  const onSubmit = (data) => {
    const payload = {
      ...data,
      avatar: data.avatar || null,
      biography: data.biography || null,
      city: data.city || null,
      birthdate: data.birthdate || null,
      username: data.username || null,
      is_public: data.is_public,
    };
    updateProfile(payload);
  };

  return (
    <div className="w-full flex-1 bg-surface rounded-3xl p-6 md:p-8 shadow-sm border border-border">
      <h3 className="text-xl font-black text-text-highlight mb-6 border-b border-slate-100 pb-4">
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

        <h4 className="text-base font-black text-text-highlight">Perfil público</h4>

        <InputField
          label="Nombre de usuario"
          type="text"
          icon={AtSign}
          placeholder="tu_nombre"
          autoComplete="off"
          registration={register('username')}
          error={errors.username}
          disabled={isPending}
        />

        <div>
          <label className="flex cursor-pointer items-start gap-2 text-sm text-text-highlight">
            <input
              type="checkbox"
              className="mt-0.5 h-4 w-4 shrink-0 accent-blue-600"
              disabled={isPending}
              {...register('is_public')}
            />
            <span className="flex flex-col">
              <span className="font-bold">Hacer público mi perfil</span>
              <span className="text-text-normal">
                Cualquiera podrá ver en /u/tu_nombre tu avatar, tu biografía, tus mejores notas por categoría, tus listas públicas y tus reseñas. Tu correo, tu ciudad y tu fecha de nacimiento nunca se muestran.
              </span>
            </span>
          </label>
          {errors.is_public && <span className="mt-1 text-xs text-red-500">{errors.is_public.message}</span>}
        </div>

        {profile?.username && (
          <Link to={`/u/${profile.username}`} className="font-bold text-primary hover:underline">
            Ver mi perfil público
          </Link>
        )}

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

export default ProfileEditForm;