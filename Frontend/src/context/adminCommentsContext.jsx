import { createContext, useMemo, useState } from 'react';
import { useAdminCommentsQuery, useAdminHideCommentMutation, useAdminUnhideCommentMutation } from '../hooks/admin/index.js';

export const AdminCommentsContext = createContext(null);

export const AdminCommentsProvider = ({ children }) => {
  const [itemIdInput, setItemIdInput] = useState('');
  const [itemId, setItemId] = useState('');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(15);

  const commentsQuery = useAdminCommentsQuery({ itemId, page, perPage });
  const hideMutation = useAdminHideCommentMutation();
  const unhideMutation = useAdminUnhideCommentMutation();

  const comments = commentsQuery.data?.comments ?? [];
  const meta = commentsQuery.data?.meta ?? null;

  const currentPage = meta?.current_page ?? page;
  const lastPage = meta?.last_page ?? 1;
  const total = meta?.total ?? comments.length;
  const canGoPrev = currentPage > 1;
  const canGoNext = currentPage < lastPage;
  const isMutatingComments = hideMutation.isPending || unhideMutation.isPending;

  const updateItemIdInput = (value) => {
    setItemIdInput(value);
  };

  const applyItemFilter = () => {
    setItemId(itemIdInput.trim());
    setPage(1);
  };

  const clearItemFilter = () => {
    setItemIdInput('');
    setItemId('');
    setPage(1);
  };

  const updatePerPage = (value) => {
    const safePerPage = Number(value) || 15;
    setPerPage(safePerPage);
    setPage(1);
  };

  const goToFirstPage = () => setPage(1);
  const goToPreviousPage = () => setPage((prevPage) => Math.max(1, prevPage - 1));
  const goToNextPage = () => setPage((prevPage) => Math.min(lastPage, prevPage + 1));
  const goToLastPage = () => setPage(lastPage);

  const hideComment = async ({ commentId, reason }) => {
    await hideMutation.mutateAsync({ commentId, reason });
  };

  const unhideComment = async ({ commentId }) => {
    await unhideMutation.mutateAsync({ commentId });
  };

  const value = useMemo(
    () => ({
      itemIdInput,
      itemId,
      comments,
      total,
      currentPage,
      lastPage,
      canGoPrev,
      canGoNext,
      perPage,
      isLoadingComments: commentsQuery.isLoading,
      isCommentsError: commentsQuery.isError,
      commentsError: commentsQuery.error,
      isFetchingComments: commentsQuery.isFetching,
      isMutatingComments,
      updateItemIdInput,
      applyItemFilter,
      clearItemFilter,
      updatePerPage,
      goToFirstPage,
      goToPreviousPage,
      goToNextPage,
      goToLastPage,
      hideComment,
      unhideComment,
    }),
    [
      itemIdInput,
      itemId,
      comments,
      total,
      currentPage,
      lastPage,
      canGoPrev,
      canGoNext,
      perPage,
      commentsQuery.isLoading,
      commentsQuery.isError,
      commentsQuery.error,
      commentsQuery.isFetching,
      isMutatingComments,
    ],
  );

  return <AdminCommentsContext.Provider value={value}>{children}</AdminCommentsContext.Provider>;
};

