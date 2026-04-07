import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { toast } from 'react-hot-toast';
import { useProfileContext } from '../../hooks/useProfileContext';
import { useUpdateProfileMutation } from '../../hooks/useUpdateProfileMutation';
import { useApiErrorHandler } from '../../hooks/useApiErrorHandler';

const profileFormSchema = z.object({
  avatar: z.string().trim().url('Introduce una URL válida.').or(z.literal('')),
  biography: z.string().max(500, 'Máximo 500 caracteres.').or(z.literal('')),
  city: z.string().max(100, 'Máximo 100 caracteres.').or(z.literal('')),
  birthdate: z.string().or(z.literal('')).refine((val) => {
    if (!val) return true;
    return new Date(val) < new Date();
  }, 'La fecha debe ser anterior a hoy.'),
  socialLinksInput: z.string().or(z.literal('')),
});

const FieldError = ({ message }) => message ? <p className="text-xs text-red-300">{message}</p> : null;

export const ProfileForm = () => {
  const { profile, updateProfile, isUpdatingProfile } = useProfileContext();

  const { register, handleSubmit, reset, setError, formState: { errors, isSubmitting } } = useForm({
    resolver: zodResolver(profileFormSchema),
    defaultValues: {
      avatar: '', biography: '', city: '', birthdate: '', socialLinksInput: '',
    },
  });

  // Sincronizamos el formulario cuando cargan los datos del perfil
  useEffect(() => {
    if (profile) {
      reset({
        avatar: profile.avatar ?? '',
        biography: profile.biography ?? '',
        city: profile.city ?? '',
        birthdate: profile.birthdate ?? '',
        socialLinksInput: profile.social_links?.join('\n') ?? '',
      });
    }
  }, [profile, reset]);

  const onSubmit = async (values) => {
    try {
      const response = await updateProfile(values);
      toast.success(response?.message ?? 'Perfil actualizado.');
    } catch (error) {
      const details = getApiValidationDetails(error);

      Object.entries(details).forEach(([field, msgs]) => {
        const key = field === 'social_links' ? 'socialLinksInput' : field;
        setError(key, { type: 'server', message: msgs[0] });
      });

      toast.error(getApiErrorMessage(error));
    }
  };

  return (
    <form className="space-y-4 rounded-xl border border-slate-800 bg-slate-900 p-5" onSubmit={handleSubmit(onSubmit)}>
      <label className="block space-y-1 text-sm">
        <span>Avatar (URL)</span>
        <input className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100" {...register('avatar')} type="url" />
        <FieldError message={errors.avatar?.message} />
      </label>

      <label className="block space-y-1 text-sm">
        <span>Biografía</span>
        <textarea className="min-h-24 w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100" {...register('biography')} />
        <FieldError message={errors.biography?.message} />
      </label>

      <div className="grid gap-4 sm:grid-cols-2">
        <label className="block space-y-1 text-sm">
          <span>Ciudad</span>
          <input className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100" {...register('city')} />
          <FieldError message={errors.city?.message} />
        </label>
        <label className="block space-y-1 text-sm">
          <span>Fecha de nacimiento</span>
          <input className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100" {...register('birthdate')} type="date" />
          <FieldError message={errors.birthdate?.message} />
        </label>
      </div>

      <label className="block space-y-1 text-sm">
        <span>Enlaces sociales (uno por línea)</span>
        <textarea className="min-h-20 w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100" {...register('socialLinksInput')} />
        <FieldError message={errors.socialLinksInput?.message} />
      </label>

      <button
        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
        disabled={isSubmitting || isUpdatingProfile}
        type="submit"
      >
        {isSubmitting || isUpdatingProfile ? 'Guardando...' : 'Guardar perfil'}
      </button>
    </form>
  );
};