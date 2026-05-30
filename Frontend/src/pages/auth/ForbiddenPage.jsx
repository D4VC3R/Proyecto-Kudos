import FeedbackState from "../../components/ui/FeedbackState.jsx";
import {Ban} from 'lucide-react'
import {useNavigate} from "react-router-dom";
import FadeUp from "../../components/animations/FadeUp.jsx";


const ForbiddenPage = () => {
  const navigate = useNavigate();

  return (
    <FadeUp>
      <FeedbackState
        icon={Ban}
        iconColorClass={'bg-red-100 text-red-500'}
        title={'¡Alto ahí, individuo!'}
        description={'Vuelve a un sitio seguro haciendo clic en el botón de aquí abajo.'}
        actionText="Justo aquí"
        onAction={() => navigate('/')}
      />
    </FadeUp>
  );
}
export default ForbiddenPage;