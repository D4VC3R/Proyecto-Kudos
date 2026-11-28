import React from 'react';
import { useLocation } from 'react-router-dom';
// Componentes
import LoginForm  from '../../components/auth/LoginForm.jsx';
import RegisterForm from '../../components/auth/RegisterForm.jsx';
import Card from '../../components/ui/Card.jsx';
import PageHeader from '../../components/ui/PageHeader.jsx';
import Tabs from '../../components/ui/Tabs.jsx';
import FadeUp from '../../components/animations/FadeUp.jsx';
import LogoMark from '../../components/layout/LogoMark.jsx';

const AuthPage = () => {
  const location = useLocation();
  const isLogin = location.pathname !== '/register';

  return (
    <Card className="mx-auto flex w-full max-w-5xl overflow-hidden p-0">
      <div className="hidden flex-col justify-between gap-12 bg-band p-10 text-band-ink md:flex md:w-5/12 lg:p-12">
        <LogoMark size={48} />
        <div>
          <p className="font-display text-h2">Únete a la comunidad</p>
          <p className="mt-4 text-body text-band-muted">
            Lleva el diario de tus pelis, series, juegos y destinos, descubre joyas ocultas y suma kudos para subir en el ranking.
          </p>
        </div>
      </div>

      <div className="flex w-full flex-col justify-center p-6 sm:p-10 md:w-7/12 lg:p-14">
        <PageHeader
          title={isLogin ? 'Iniciar sesión' : 'Crear cuenta'}
          subtitle={isLogin ? 'Inicia sesión para continuar votando.' : 'Únete a la comunidad Kudos.'}
        />

        <Tabs
          label="Acceso"
          activeId={isLogin ? 'login' : 'register'}
          items={[
            { id: 'login', label: 'Ya tengo cuenta', to: '/login', state: location.state },
            { id: 'register', label: 'No tengo cuenta', to: '/register', state: location.state },
          ]}
          className="mb-8 mt-8"
        />

        <FadeUp key={isLogin ? 'login' : 'register'}>
          {isLogin ? <LoginForm /> : <RegisterForm />}
        </FadeUp>
      </div>
    </Card>
  );
};

export default AuthPage;
