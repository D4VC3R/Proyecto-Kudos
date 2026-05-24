import React from 'react';

export class AppErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError() {
    return { hasError: true };
  }

  componentDidCatch(error) {
    // Para debug. TODO: Eliminar en despliegue.
    console.error('AppErrorBoundary capturo un error:', error);
  }

  render() {
    if (this.state.hasError) {
      return (
        <main className="mx-auto mt-16 w-full max-w-xl rounded-2xl border border-slate-800 bg-slate-900 p-8 text-center text-slate-100">
          <h1 className="text-2xl font-bold">Se produjo un error inesperado</h1>
          <p className="mt-2 text-slate-300">Recarga la pagina. Si persiste, revisa la consola del navegador.</p>
        </main>
      );
    }

    return this.props.children;
  }
}

