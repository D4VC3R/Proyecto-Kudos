import React from 'react';
import { useLocation, Link } from 'react-router-dom';
import clsx from 'clsx';
import { AnimatePresence } from 'framer-motion';
import { Gamepad2 } from 'lucide-react';
// Componentes
import LoginForm  from '../../components/auth/LoginForm.jsx';
import RegisterForm from '../../components/auth/RegisterForm.jsx';
import SlideTransition from '../../components/animations/SlideTransition.jsx';

const AuthPage = () => {
  const location = useLocation();
  const isLogin = location.pathname !== '/register';

  return (
      <div className="flex w-full flex-col md:flex-row min-h-[80vh] bg-surface rounded-3xl shadow-xl ring-1 ring-slate-200 overflow-hidden mx-auto max-w-5xl my-4">
        <div className="hidden md:flex md:w-1/2 flex-col justify-center items-center bg-primary p-12 text-text-btn relative overflow-hidden">
          <div className="absolute top-10 left-10 w-24 h-24 bg-blue-500 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
          <div className="absolute top-10 right-10 w-24 h-24 bg-blue-400 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
          <div className="absolute -bottom-8 left-20 w-32 h-32 bg-blue-700 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

          <div className="relative z-10 flex flex-col items-center text-center">
            <Gamepad2 size={80} className="mb-6 text-text-btn drop-shadow-md" />
            <h2 className="text-4xl font-black mb-4">Únete a la comunidad</h2>
            <p className="text-blue-100 text-lg">
              Valora candidatos, descubre joyas ocultas y acumula Kudos para subir en el ránking global de usuarios.
            </p>
          </div>
        </div>

        <div className="flex w-full md:w-1/2 flex-col justify-center p-8 sm:p-12 lg:p-16 relative">
          <div className="mb-8">
            <h1 className="text-3xl font-black tracking-tight text-text-highlight">
              {isLogin ? 'Iniciar Sesión' : 'Crear Cuenta'}
            </h1>
            <p className="mt-2 text-text-normal">
              {isLogin ? 'Inicia sesión para continuar votando.' : 'Únete a la comunidad Kudos.'}
            </p>
          </div>

          <div className="mb-8 flex rounded-xl bg-slate-100 p-1">
            <Link
                to="/login"
                className={clsx(
                    "flex-1 rounded-lg py-2.5 text-center text-sm font-bold transition-all",
                    isLogin ? "bg-surface text-primary shadow-sm" : "text-text-normal hover:text-slate-700"
                )}
            >
              Ya tengo cuenta
            </Link>
            <Link
                to="/register"
                className={clsx(
                    "flex-1 rounded-lg py-2.5 text-center text-sm font-bold transition-all",
                    !isLogin ? "bg-surface text-primary shadow-sm" : "text-text-normal hover:text-slate-700"
                )}
            >
              No tengo cuenta
            </Link>
          </div>

          <div className="relative min-h-[300px]">
            <AnimatePresence mode="wait">
              {isLogin ? (
                  <SlideTransition key="login" animationKey="login" direction="left">
                    <LoginForm />
                  </SlideTransition>
              ) : (
                  <SlideTransition key="register" animationKey="register" direction="right">
                    <RegisterForm />
                  </SlideTransition>
              )}
            </AnimatePresence>
          </div>
        </div>
      </div>
  );
};

export default AuthPage;
