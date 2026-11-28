import { Link } from "react-router-dom";
import ErrorState from "../components/ui/ErrorState.jsx";
import Button from "../components/ui/Button.jsx";
import FadeUp from "../components/animations/FadeUp.jsx";


const ErrorPage = () => (
  <FadeUp>
    <ErrorState
      kicker="Error 404"
      title="No encontramos esta página"
      description="Puede que el enlace esté mal escrito o que la página ya no exista."
    >
      <Button as={Link} to="/" size="lg">Volver al inicio</Button>
    </ErrorState>
  </FadeUp>
);

export default ErrorPage;
