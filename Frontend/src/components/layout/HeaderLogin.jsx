import React from 'react';
import {useForm} from 'react-hook-form';
import {Link} from 'react-router-dom';
import {useLogin} from '../../hooks/auth/useAuthMutations.js';
import Button from '../ui/Button.jsx';
import {zodResolver} from "@hookform/resolvers/zod";
import {loginSchema} from "../../lib/schemas.js";

const HeaderLogin = () => {
  const {mutate: login, isPending} = useLogin();
  const {register, handleSubmit} = useForm({
    resolver: zodResolver(loginSchema)
  });

  const onSubmit = (data) => {
    login(data);
  };

  return (
    <div className="flex items-center gap-4">
      <form onSubmit={handleSubmit(onSubmit)} className="hidden md:flex items-center gap-2">
        <input
          type="email"
          {...register('email', {required: true})}
          placeholder="Email"
          disabled={isPending}
          className="rounded-lg border border-border px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
        />
        <input
          type="password"
          {...register('password', {required: true})}
          placeholder="Contraseña"
          disabled={isPending}
          className="rounded-lg border border-border px-2 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
        />
        <Button
          type="submit"
          isLoading={isPending}
          variant="solid"
          color="primary"
          size="sm"
          radius="lg"
        >
          Entrar
        </Button>
      </form>
      <div className="flex items-center gap-2 text-sm font-medium">
        <Link to="/login"
              className="md:hidden text-slate-700 hover:text-nav-hover-text font-bold whitespace-nowrap px-3">
          Entrar
        </Link>
        <span className="hidden md:block text-slate-400">o</span>
        <Link to="/register" className="text-primary hover:text-blue-700 font-bold whitespace-nowrap">
          Regístrate
        </Link>
      </div>
    </div>
  );
};

export default HeaderLogin;