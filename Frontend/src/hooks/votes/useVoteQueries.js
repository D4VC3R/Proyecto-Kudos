import { useQuery, useInfiniteQuery, keepPreviousData } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

export const VOTE_KEYS = {
  all: ['votes'],
  myVotesList: () => [...VOTE_KEYS.all, 'my-votes'],
  myVotes: (filters) => [...VOTE_KEYS.myVotesList(), { filters }],
  infiniteMyVotes: (filters) => [...VOTE_KEYS.myVotesList(), 'infinite', { filters }],
};

export const useMyVotes = (filters = {}) => {
  return useQuery({
    queryKey: VOTE_KEYS.myVotes(filters),
    queryFn: () => axiosClient.get('/votes/my-votes', { params: filters }),
    placeholderData: keepPreviousData,
  });
};

export const useInfiniteMyVotes = (filters = {}, perPage = 15) => {
  return useInfiniteQuery({
    queryKey: VOTE_KEYS.infiniteMyVotes(filters),
    queryFn: ({ pageParam }) =>
      axiosClient.get('/votes/my-votes', { params: { ...filters, page: pageParam, per_page: perPage } }),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      if (!lastPage.meta) return undefined;
      return lastPage.meta.current_page < lastPage.meta.last_page
        ? lastPage.meta.current_page + 1
        : undefined;
    },
    placeholderData: keepPreviousData,
  });
};
