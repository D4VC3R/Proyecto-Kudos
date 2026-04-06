import { createContext, useMemo } from 'react';
import { useParams } from 'react-router-dom';
import { selectUser, useSessionStore } from '../store/useSessionStore';
import {
    useAdminBanUserMutation,
    useAdminRevokeSessionsMutation,
    useAdminUnbanUserMutation,
    useAdminUserDetailQuery,
} from '../hooks/admin/domains/usersAdminHooks';

export const AdminUserDetailContext = createContext(null);

export const AdminUserDetailProvider = ({ children }) => {
    const { userId } = useParams();
    const currentUser = useSessionStore(selectUser);

    const detailQuery = useAdminUserDetailQuery({ userId });
    const banMutation = useAdminBanUserMutation();
    const unbanMutation = useAdminUnbanUserMutation();
    const revokeMutation = useAdminRevokeSessionsMutation();

    const user = detailQuery.data?.data ?? null;
    const isSelfUser = user?.id && currentUser?.id === user.id;

    const isMutating = banMutation.isPending || unbanMutation.isPending || revokeMutation.isPending;

    const banUser = async ({ reason, isPermanent, days }) => {
        if (!userId) return;
        const payload = isPermanent ? { reason, is_permanent: true } : { reason, is_permanent: false, days };
        await banMutation.mutateAsync({ userId, payload });
    };

    const unbanUser = async () => {
        if (!userId) return;
        await unbanMutation.mutateAsync({ userId });
    };

    const revokeSessions = async () => {
        if (!userId) return;
        await revokeMutation.mutateAsync({ userId });
    };

    const value = useMemo(
        () => ({
            user, isSelfUser,
            isLoadingUserDetail: detailQuery.isLoading,
            isUserDetailError: detailQuery.isError,
            userDetailError: detailQuery.error,
            isFetchingUserDetail: detailQuery.isFetching,
            isMutating,
            banUser, unbanUser, revokeSessions,
        }),
        [user, isSelfUser, detailQuery.isLoading, detailQuery.isError, detailQuery.error, detailQuery.isFetching, isMutating],
    );

    return <AdminUserDetailContext.Provider value={value}>{children}</AdminUserDetailContext.Provider>;
};