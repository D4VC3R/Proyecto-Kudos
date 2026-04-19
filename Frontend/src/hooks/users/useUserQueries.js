import { useQuery, keepPreviousData } from '@tanstack/react-query';
import axiosClient from './../../lib/axiosClient';

export const USER_KEYS = {
  profile: ['profile'],
  minimalProfile: ['profile', 'minimal'],
  ranking: ['users', 'ranking'],
  statistics: ['profile', 'statistics'],
};

export const useProfile = () => {
  return useQuery({
    queryKey: USER_KEYS.profile,
    queryFn: () => axiosClient.get('/profile'),
    select: (response) => response.data,
  });
};

export const useMinimalProfile = () => {
  return useQuery({
    queryKey: USER_KEYS.minimalProfile,
    queryFn: () => axiosClient.get('/profile/minimal'),
    select: (response) => response.data,
  });
};

export const useProfileStatistics = () => {
  return useQuery({
    queryKey: USER_KEYS.statistics,
    queryFn: () => axiosClient.get('/profile/statistics'),
    select: (response) => response.data,
  });
};

export const useUserRanking = (page = 1) => {
  return useQuery({
    queryKey: [...USER_KEYS.ranking, page],
    queryFn: () => axiosClient.get('/users/ranking', { params: { page } }),
    placeholderData: keepPreviousData,
  });
};