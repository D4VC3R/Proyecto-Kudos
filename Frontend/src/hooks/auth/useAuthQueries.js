import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';

export const useVerifyEmail = (verifyUrl) => {
    return useQuery({
        queryKey: ['verifyEmail', verifyUrl],
        queryFn: () => axiosClient.get(verifyUrl),
        enabled: !!verifyUrl,
        retry: false,
        staleTime: Infinity,
    });
};