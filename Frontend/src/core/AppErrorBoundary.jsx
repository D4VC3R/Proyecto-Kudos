import React from 'react';
import ErrorState from "../components/ui/ErrorState.jsx";
import Button from "../components/ui/Button.jsx";

/**
 * Pantalla de error, evita mostrar la página en blanco
 * si ocurre un error no controlado.
 * */
export class AppErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false };
  }

  static getDerivedStateFromError() {
    return { hasError: true };
  }

  componentDidCatch(error, info) {
    console.error('Error no controlado en la interfaz:', error, info?.componentStack);
  }

  // Propiedad flecha para que `this` no se pierda al pasarlo como manejador.
  handleReload = () => {
    window.location.reload();
  };

  render() {
    if (this.state.hasError) {
      // Este límite está fuera de App: no hay cabecera ni <main>, así que pone el suyo. «Volver al inicio» es un <a>
      // normal porque, al navegar dentro de la SPA, el límite seguiría mostrando el error.
      return (
        <main id="contenido" className="mx-auto flex min-h-screen w-full max-w-content items-center justify-center px-4 py-8 md:px-6">
          <ErrorState
            title="Algo ha fallado"
            description="Se ha producido un error inesperado. Recarga la página para seguir navegando."
          >
            <Button size="lg" onClick={this.handleReload}>Recargar página</Button>
            <Button as="a" href="/" size="lg" variant="secondary">Volver al inicio</Button>
          </ErrorState>
        </main>
      );
    }

    return this.props.children;
  }
}
