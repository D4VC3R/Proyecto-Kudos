import React from 'react';
import { Link } from 'react-router-dom';
import {  UserPlus } from 'lucide-react';
import { RegisterForm } from '../components/auth/RegisterForm';
import { FadeUp } from '../components/animations/FadeUp.jsx';

const RegisterPage = () => {
  return (
    <div className="flex min-h-[85vh] w-full flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
      <FadeUp className="w-full max-w-md rounded-3xl bg-white p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200">
        <div className="mb-8 flex flex-col items-center text-center">
          <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-100 text-blue-600 shadow-inner">
            <UserPlus size={32} strokeWidth={2.5} />
          </div>
          <h1 className="text-3xl font-black text-slate-900 tracking-tight">
            Únete al <span className="text-blue-600 drop-shadow-sm">Juego</span>
          </h1>
          <p className="mt-2 text-sm md:text-base font-medium text-slate-500">
            Regístrate para votar, proponer categorías y coleccionar Kudos.
          </p>
        </div>

        <RegisterForm />

        <div className="mt-8 text-center text-sm font-medium text-slate-500">
          ¿Ya tienes una cuenta?{' '}
          <Link to="/login" className="font-bold text-blue-600 hover:text-blue-700 hover:underline underline-offset-2 transition-colors">
            Entra aquí
          </Link>
        </div>
      </FadeUp>
    </div>
  );
};

export default RegisterPage;

