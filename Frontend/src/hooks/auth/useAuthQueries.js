import { useQuery } from '@tanstack/react-query';
import axiosClient from '../../core/axiosClient.js';

export const useVerifyEmail = (verifyUrl) => {
    return useQuery({
        queryKey: ['verifyEmail', verifyUrl],
        queryFn: () => axiosClient.get(verifyUrl),
        enabled: !!verifyUrl,
        retry: false,
        staleTime: Infinity,
    });
};