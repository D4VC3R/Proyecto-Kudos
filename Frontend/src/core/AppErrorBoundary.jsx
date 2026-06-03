import React from 'react';
import FeedbackState from "../components/ui/FeedbackState.jsx";
import logo from "./../../public/logo.svg"

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

  componentDidCatch(error) {
    console.error('Error chungo: ', error);
  }

  render() {
    if (this.state.hasError) {
      return (
          <div className="flex min-h-[50vh] w-full items-center justify-center p-4">
            <FeedbackState
                icon={logo}
                iconColorClass="bg-red-100 text-red-600"
                title="Esto no pinta bien..."
                description="Estamos trabajando para volver lo más rápido posible."
                actionText="Recargar página"
                onAction={this.handleReload}
            />
          </div>
      );
    }

    return this.props.children;
  }
}

