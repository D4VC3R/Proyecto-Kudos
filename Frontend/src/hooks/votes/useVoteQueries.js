import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

export const VOTE_KEYS = {
  all: ['votes'],
  myVotesList: () => [...VOTE_KEYS.all, 'my-votes'],
  myVotes: (filters) => [...VOTE_KEYS.myVotesList(), { filters }],
};

export const useMyVotes = (filters = {}) => {
  return useQuery({
    queryKey: VOTE_KEYS.myVotes(filters),
    queryFn: () => axiosClient.get('/votes/my-votes', { params: filters }),
  });
};