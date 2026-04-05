import { useLoginContext } from '../../hooks/useLoginContext';

export const LoginApiForm = () => {
  const { email, password, isSubmitting, updateEmail, updatePassword, submitLogin } = useLoginContext();

  return (
    <form className="space-y-3" onSubmit={submitLogin}>
      <label className="block space-y-1 text-sm">
        <span>Email</span>
        <input
          className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100"
          onChange={(event) => updateEmail(event.target.value)}
          type="email"
          value={email}
        />
      </label>

      <label className="block space-y-1 text-sm">
        <span>Contrasena</span>
        <input
          className="w-full rounded-md border border-slate-700 bg-slate-950 px-3 py-2 text-slate-100"
          onChange={(event) => updatePassword(event.target.value)}
          type="password"
          value={password}
        />
      </label>

      <button
        className="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70"
        disabled={isSubmitting}
        type="submit"
      >
        {isSubmitting ? 'Entrando...' : 'Entrar con API'}
      </button>
    </form>
  );
};
