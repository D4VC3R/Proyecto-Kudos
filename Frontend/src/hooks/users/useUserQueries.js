import { useQuery } from '@tanstack/react-query';
import axiosClient from './../../lib/axiosClient';

export const USER_KEYS = {
  profile: ['profile'],
  ranking: ['users', 'ranking'],
};

export const useProfile = () => {
  return useQuery({
    queryKey: USER_KEYS.profile,
    queryFn: () => axiosClient.get('/profile'),
    select: (response) => response.data,
  });
};

export const useUserRanking = (page = 1) => {
  return useQuery({
    queryKey: [...USER_KEYS.ranking, page],
    queryFn: () => axiosClient.get('/users/ranking', { params: { page } }),
  });
};