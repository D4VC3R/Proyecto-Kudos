import { LoginApiForm } from '../components/login/LoginApiForm';
import { LoginHeader } from '../components/login/LoginHeader';
import { LoginProvider } from '../context/loginContext';

export const LoginPage = () => {
  return (
    <LoginProvider>
      <section className="mx-auto w-full max-w-md space-y-4 rounded-2xl border border-slate-800 bg-slate-900 p-6">
        <LoginHeader />
        <LoginApiForm />
      </section>
    </LoginProvider>
  );
};
