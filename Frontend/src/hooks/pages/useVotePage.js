import { useState, useEffect } from 'react';
import { useParams } from 'react-router-dom';
import { useNextCategoryItem } from '../categories/useCategoryQueries.js';
import { useCreateVote } from '../votes/useVoteMutations.js';

export const useVotePage = () => {
  const { categorySlug } = useParams();
  const [showComments, setShowComments] = useState(false);

  // Peticiones de red
  const { data, isLoading, isFetching } = useNextCategoryItem(categorySlug);
  const { mutate: submitVote, isPending } = useCreateVote();

  const item = data?.data;
  const isBackgroundUpdating = isFetching && !isLoading;


  useEffect(() => {
    setShowComments(false);
  }, [item?.id]);

  const handleVote = (score) => {
    submitVote({ item_id: item.id, type: 'vote', score, categorySlug });
  };

  const handleSkip = () => {
    submitVote({ item_id: item.id, type: 'skip', categorySlug });
  };

  const toggleComments = () => {
    setShowComments((prev) => !prev);
  };

  return {
    state: {
      categorySlug,
      item,
      showComments,
      isLoading,
      isPending,
      isBackgroundUpdating,
    },
    actions: {
      handleVote,
      handleSkip,
      toggleComments,
    }
  };
};