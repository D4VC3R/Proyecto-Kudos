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
        title={'¡Enhorabuena, has encontrado un bug!'}
        description={'Por desgracia esta acción no te hace ganar Kudos, mejor vuelve a la página principal y busca otra manera de conseguirlos.'}
        actionText="Quiero más Kudos y menos bugs."
        onAction={() => navigate('/')}
      />
    </FadeUp>
  );
}

export default ErrorPage;