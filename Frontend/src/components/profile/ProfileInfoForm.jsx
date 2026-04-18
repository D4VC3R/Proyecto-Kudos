import React from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Save, User as UserIcon, MapPin, Calendar, Image as ImageIcon } from 'lucide-react';
import { useUpdateProfile } from '../../hooks/users/useUserMutations';
import { useSessionStore } from '../../store/useSessionStore';
import { profileSchema } from '../../lib/schemas/profileSchema';

export const ProfileInfoForm = ({ profile }) => {
  const user = useSessionStore((state) => state.user);
  const { mutate: updateProfile, isPending } = useUpdateProfile();

  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      avatar: profile?.avatar || '',
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
    <div className="flex flex-col gap-8 md:flex-row max-w-4xl mx-auto">
      <div className="flex flex-col items-center p-6 bg-slate-50 rounded-3xl border border-slate-200 md:w-1/3">
        <div className="w-32 h-32 rounded-full overflow-hidden bg-blue-100 text-blue-600 flex items-center justify-center mb-4 ring-4 ring-white shadow-lg">
          {profile?.avatar ? (
            <img src={profile.avatar} alt={user?.name} className="w-full h-full object-cover" />
          ) : (
            <UserIcon size={64} />
          )}
        </div>
        <h2 className="text-2xl font-black text-slate-900 text-center">{user?.name}</h2>
        <p className="text-slate-500 font-medium mb-4">{user?.email}</p>
        <div className="flex flex-wrap justify-center gap-2">
          <span className="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">
            {profile?.total_kudos || user?.total_kudos || 0} Kudos
          </span>
          {user?.role === 'admin' && (
            <span className="px-3 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">
              Admin
            </span>
          )}
        </div>
      </div>

      <div className="flex-1 bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-200">
        <h3 className="text-xl font-black text-slate-900 mb-6 border-b border-slate-100 pb-4">
          Editar Perfil
        </h3>

        <form onSubmit={handleSubmit(onSubmit)} className="flex flex-col gap-5">
          <div>
            <label className="mb-2 block text-sm font-bold text-slate-700">URL del Avatar</label>
            <div className="relative">
              <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <ImageIcon size={18} />
              </div>
              <input
                type="url"
                {...register('avatar')}
                disabled={isPending}
                className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
                placeholder="https://ejemplo.com/avatar.jpg"
              />
            </div>
            {errors.avatar && <span className="mt-1 text-xs text-red-500">{errors.avatar.message}</span>}
          </div>

          <div>
            <label className="mb-2 block text-sm font-bold text-slate-700">Biografía</label>
            <textarea
              {...register('biography')}
              disabled={isPending}
              rows={4}
              className="w-full rounded-xl border border-slate-200 bg-slate-50 p-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50 resize-none"
              placeholder="Cuéntanos un poco sobre ti..."
            />
            {errors.biography && <span className="mt-1 text-xs text-red-500">{errors.biography.message}</span>}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
              <label className="mb-2 block text-sm font-bold text-slate-700">Ciudad</label>
              <div className="relative">
                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                  <MapPin size={18} />
                </div>
                <input
                  type="text"
                  {...register('city')}
                  disabled={isPending}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
                  placeholder="Ej. Madrid"
                />
              </div>
              {errors.city && <span className="mt-1 text-xs text-red-500">{errors.city.message}</span>}
            </div>

            <div>
              <label className="mb-2 block text-sm font-bold text-slate-700">Fecha de Nacimiento</label>
              <div className="relative">
                <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                  <Calendar size={18} />
                </div>
                <input
                  type="date"
                  {...register('birthdate')}
                  disabled={isPending}
                  className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-slate-900 transition-colors focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-1 focus:ring-blue-500 disabled:opacity-50"
                />
              </div>
              {errors.birthdate && <span className="mt-1 text-xs text-red-500">{errors.birthdate.message}</span>}
            </div>
          </div>

          <div className="flex justify-end mt-4 pt-4 border-t border-slate-100">
            <button
              type="submit"
              disabled={isPending}
              className="flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 font-bold text-white transition-colors hover:bg-slate-800 disabled:opacity-50"
            >
              {isPending ? <Loader2 size={18} className="animate-spin" /> : <Save size={18} />}
              Guardar Cambios
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

