import React from 'react';
import {zodResolver} from '@hookform/resolvers/zod';
import {Save, MapPin, Calendar, AtSign, Image as ImageIcon} from 'lucide-react';
import {profileSchema} from '../../lib/schemas';
//  Componentes
import Card from '../ui/Card.jsx';
import Input from '../ui/Input.jsx';
import Textarea from '../ui/Textarea.jsx';
import Checkbox from '../ui/Checkbox.jsx';
import Button from '../ui/Button.jsx';
// Hooks
import {useForm} from 'react-hook-form';
import {useUpdateProfile} from '../../hooks/users/useUserMutations';

// El campo de fecha nativo usa el idioma del navegador; debajo se repite en español.
const BIRTHDATE_FORMAT = new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
const formatBirthdate = (value) => {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value ?? '')) return null;
  const date = new Date(`${value}T00:00:00Z`);
  return Number.isNaN(date.getTime()) ? null : BIRTHDATE_FORMAT.format(date);
};

const ProfileEditForm = ({profile}) => {
  const {register, handleSubmit, setError, watch, formState: {errors}} = useForm({
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

  const {mutate: updateProfile, isPending} = useUpdateProfile({setError});

  const birthdateText = formatBirthdate(watch('birthdate'));

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
    <Card className="w-full flex-1 p-6 md:p-8">
      <h2 className="font-display text-h2 text-ink mb-6">
        Editar perfil
      </h2>

      <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
        <Input
          label="URL del avatar"
          type="url"
          icon={ImageIcon}
          placeholder="https://ejemplo.com/avatar.jpg"
          registration={register('avatar')}
          error={errors.avatar}
          disabled={isPending}
        />

        <Textarea
          label="Biografía"
          rows={4}
          placeholder="Cuéntanos un poco sobre ti..."
          registration={register('biography')}
          error={errors.biography}
          disabled={isPending}
        />

        <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
          <Input
            label="Ciudad"
            type="text"
            icon={MapPin}
            placeholder="Ej. Madrid"
            registration={register('city')}
            error={errors.city}
            disabled={isPending}
          />

          <div>
            <Input
              label="Fecha de nacimiento"
              type="date"
              icon={Calendar}
              registration={register('birthdate')}
              error={errors.birthdate}
              disabled={isPending}
            />
            {birthdateText && (
              <p data-birthdate-hint className="mt-1 text-caption text-ink-muted">{birthdateText}</p>
            )}
          </div>
        </div>

        <h3 className="font-display text-h3 text-ink">Perfil público</h3>

        <Input
          label="Nombre de usuario"
          type="text"
          icon={AtSign}
          placeholder="tu_nombre"
          autoComplete="off"
          registration={register('username')}
          error={errors.username}
          disabled={isPending}
        />

        <Checkbox
          label="Hacer público mi perfil"
          description="Cualquiera podrá ver en /u/tu_nombre tu avatar, tu biografía, tus mejores notas por categoría, tus listas públicas y tus reseñas. Tu correo, tu ciudad y tu fecha de nacimiento nunca se muestran."
          registration={register('is_public')}
          error={errors.is_public}
          disabled={isPending}
        />

        <div className="flex justify-end mt-4 pt-4 border-t border-line">
          <Button
            type="submit"
            isLoading={isPending}
            variant="primary"
            icon={Save}
          >
            Guardar cambios
          </Button>
        </div>
      </form>
    </Card>
  );
};

export default ProfileEditForm;