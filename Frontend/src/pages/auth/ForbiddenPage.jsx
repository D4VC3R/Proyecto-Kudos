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
        title={'No tienes acceso a esta página'}
        description={'Tu cuenta no tiene permiso para ver este contenido.'}
        actionText="Volver al inicio"
        onAction={() => navigate('/')}
      />
    </FadeUp>
  );
}
export default ForbiddenPage;