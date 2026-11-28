import { Link } from "react-router-dom";
import ErrorState from "../../components/ui/ErrorState.jsx";
import Button from "../../components/ui/Button.jsx";
import FadeUp from "../../components/animations/FadeUp.jsx";


const ForbiddenPage = () => (
  <FadeUp>
    <ErrorState
      kicker="Error 403"
      title="No tienes acceso a esta página"
      description="Tu cuenta no tiene permiso para ver este contenido."
    >
      <Button as={Link} to="/" size="lg">Volver al inicio</Button>
    </ErrorState>
  </FadeUp>
);
export default ForbiddenPage;
