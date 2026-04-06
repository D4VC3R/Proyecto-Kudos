import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { useApiErrorHandler } from '../useApiErrorHandler';

export const adminQueryScopes = {
    users: ['admin-users'],
    userDetail: ['admin-user-detail'],
    items: ['admin-items'],
    proposals: ['admin-proposals'],
    comments: ['admin-comments'],
};

export const adminQueryKeys = {
    users: (params) => [...adminQueryScopes.users, ...Object.values(params)],
    usersSummary: () => ['admin-users-summary'],
    userDetail: (userId) => [...adminQueryScopes.userDetail, userId],
    items: (params) => [...adminQueryScopes.items, ...Object.values(params)],
    proposals: (params) => [...adminQueryScopes.proposals, ...Object.values(params)],
    comments: (params) => [...adminQueryScopes.comments, ...Object.values(params)],
};

export const useAdminMutation = ({ mutationFn, defaultSuccessMessage, invalidateQueryKeys = [] }) => {
    const queryClient = useQueryClient();
    const { handleApiError } = useApiErrorHandler();

    return useMutation({
        mutationFn,
        onSuccess: async (responseData) => {
            toast.success(responseData?.message ?? defaultSuccessMessage);

            await Promise.all(
                invalidateQueryKeys.map((queryKey) => {
                    return queryClient.invalidateQueries({ queryKey });
                }),
            );
        },
        onError: (error) => {
            handleApiError({ error, notify: (message) => toast.error(message) });
        },
    });
};