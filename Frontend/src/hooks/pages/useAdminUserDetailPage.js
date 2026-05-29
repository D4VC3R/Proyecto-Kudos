import { useParams, useNavigate } from 'react-router-dom';
import { useAdminUserDetail } from './../admin/useAdminQueries';
import { useAdminUserActions } from './../admin/useAdminActions';

export const useAdminUserDetailPage = () => {
  const { userId } = useParams();
  const navigate = useNavigate();

  const { data: user, isLoading, isError } = useAdminUserDetail(userId);
  const modalActions = useAdminUserActions();

  const handleGoBack = () => navigate('/admin/users');

  return {
    state: {
      user,
      isLoading,
      isError,
      ...modalActions,
    },
    actions: {
      handleGoBack,
      ...modalActions,
    }
  };
};