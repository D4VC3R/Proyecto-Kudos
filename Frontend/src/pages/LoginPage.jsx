import React from 'react';
import { Link } from 'react-router-dom';
import { LogIn } from 'lucide-react';
import { LoginForm } from '../components/auth/LoginForm';
import { FadeUp } from '../components/animations/FadeUp';

export const LoginPage = () => {
  return (
    <div className="flex min-h-[85vh] w-full flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <FadeUp className="w-full max-w-md rounded-3xl bg-white p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200">
        <div className="mb-8 flex flex-col items-center text-center">
          <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 shadow-inner">
            <LogIn size={32} strokeWidth={2.5} />
          </div>
          <h1 className="text-3xl font-black text-slate-900 tracking-tight">
            Bienvenido de <span className="text-blue-600 drop-shadow-sm">Vuelta</span>
          </h1>
          <p className="mt-2 text-sm md:text-base font-medium text-slate-500">
            Inicia sesión para continuar votando y acumulando Kudos.
          </p>
        </div>

        <LoginForm />

        <div className="mt-8 text-center text-sm font-medium text-slate-500">
          ¿Aún no tienes cuenta?{' '}
          <Link to="/register" className="font-bold text-blue-600 hover:text-blue-700 hover:underline underline-offset-2 transition-colors">
            Juega ahora
          </Link>
        </div>
      </FadeUp>
    </div>
  );
};

export default LoginPage;

