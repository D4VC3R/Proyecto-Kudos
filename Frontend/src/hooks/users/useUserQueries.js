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

export const useUserRanking = () => {
  return useQuery({
    queryKey: USER_KEYS.ranking,
    queryFn: () => axiosClient.get('/users/ranking'),
    select: (response) => response.data,
  });
};