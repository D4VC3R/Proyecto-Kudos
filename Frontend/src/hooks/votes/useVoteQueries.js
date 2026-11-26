import { useInfiniteQuery, keepPreviousData } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';

export const VOTE_KEYS = {
  all: ['votes'],
  myVotesList: () => [...VOTE_KEYS.all, 'my-votes'],
  infiniteMyVotes: (filters) => [...VOTE_KEYS.myVotesList(), 'infinite', { filters }],
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
    staleTime: 1000 * 60 * 5,
  });
};
