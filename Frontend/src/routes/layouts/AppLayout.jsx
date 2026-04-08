import { Outlet } from 'react-router-dom';


export const AppLayout = () => {
  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">

      <main className="mx-auto w-full max-w-6xl px-4 py-8">
        <Outlet />
      </main>
    </div>
  );
};