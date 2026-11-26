import FeedbackState from "../components/ui/FeedbackState.jsx";
import {useNavigate} from "react-router-dom";
import {Ban} from "lucide-react";
import FadeUp from "../components/animations/FadeUp.jsx";


const ErrorPage = () => {
  const navigate = useNavigate();

  return (
    <FadeUp>
      <FeedbackState
        icon={Ban}
        iconColorClass={'bg-red-100 text-red-500'}
        title={'No encontramos esta página'}
        description={'Puede que el enlace esté mal escrito o que la página ya no exista.'}
        actionText="Volver al inicio"
        onAction={() => navigate('/')}
      />
    </FadeUp>
  );
}

export default ErrorPage;