import React from 'react';
import FeedbackState from "../components/ui/FeedbackState.jsx";
import { AlertTriangle } from "lucide-react";

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
      return (
          <div className="flex min-h-[50vh] w-full items-center justify-center p-4">
            <FeedbackState
                icon={AlertTriangle}
                iconColorClass="bg-red-100 text-red-600"
                title="Algo ha fallado"
                description="Se ha producido un error inesperado. Recarga la página para seguir navegando."
                actionText="Recargar página"
                onAction={this.handleReload}
            />
          </div>
      );
    }

    return this.props.children;
  }
}

