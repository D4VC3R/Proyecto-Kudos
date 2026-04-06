import { createContext, useMemo, useState } from 'react';
import { useAdminCommentsQuery, useAdminHideCommentMutation, useAdminUnhideCommentMutation } from '../hooks/admin/domains/commentsAdminHooks';
import { useTableState } from '../hooks/useTableState';

export const AdminCommentsContext = createContext(null);

export const AdminCommentsProvider = ({ children }) => {
    const tableState = useTableState(15);
    const [itemIdInput, setItemIdInput] = useState('');
    const [itemId, setItemId] = useState('');

    const commentsQuery = useAdminCommentsQuery({
        itemId,
        page: tableState.page,
        perPage: tableState.perPage,
    });

    const hideMutation = useAdminHideCommentMutation();
    const unhideMutation = useAdminUnhideCommentMutation();

    const comments = commentsQuery.data?.data ?? [];
    const meta = commentsQuery.data?.meta ?? null;

    const currentPage = tableState.page;
    const lastPage = meta?.last_page ?? 1;
    const total = meta?.total ?? comments.length;
    const canGoPrev = currentPage > 1;
    const canGoNext = currentPage < lastPage;
    const isMutatingComments = hideMutation.isPending || unhideMutation.isPending;

    const applyItemFilter = () => {
        setItemId(itemIdInput.trim());
        tableState.goToPage(1);
    };

    const clearItemFilter = () => {
        setItemIdInput('');
        setItemId('');
        tableState.goToPage(1);
    };

    const hideComment = async ({ commentId, reason }) => {
        await hideMutation.mutateAsync({ commentId, reason });
    };

    const unhideComment = async ({ commentId }) => {
        await unhideMutation.mutateAsync({ commentId });
    };

    const value = useMemo(
        () => ({
            itemIdInput, itemId, comments, total, currentPage, lastPage, canGoPrev, canGoNext,
            perPage: tableState.perPage,
            isLoadingComments: commentsQuery.isLoading,
            isCommentsError: commentsQuery.isError,
            commentsError: commentsQuery.error,
            isFetchingComments: commentsQuery.isFetching,
            isMutatingComments,
            updateItemIdInput: setItemIdInput,
            applyItemFilter, clearItemFilter,
            updatePerPage: tableState.updatePerPage,
            goToFirstPage: () => tableState.goToPage(1),
            goToPreviousPage: () => tableState.goToPage(Math.max(1, currentPage - 1)),
            goToNextPage: () => tableState.goToPage(Math.min(lastPage, currentPage + 1)),
            goToLastPage: () => tableState.goToPage(lastPage),
            hideComment, unhideComment,
        }),
        [
            itemIdInput, itemId, comments, total, currentPage, lastPage, canGoPrev, canGoNext,
            tableState, commentsQuery.isLoading, commentsQuery.isError, commentsQuery.error,
            commentsQuery.isFetching, isMutatingComments,
        ],
    );

    return <AdminCommentsContext.Provider value={value}>{children}</AdminCommentsContext.Provider>;
};