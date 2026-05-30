import { useMemo } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useInfiniteCategoryRanking } from '../categories/useCategoryQueries.js';
import { useInfiniteItems } from '../items/useItemQueries.js';

export const useCategoryPage = () => {
  const { categorySlug } = useParams();
  const navigate = useNavigate();
  const itemsPerPage = 12;

  const {
    data: rankingData,
    isLoading: isLoadingRanking,
    isFetching: isFetchingRanking,
    isFetchingNextPage: isFetchingNextRanking,
    hasNextPage: hasNextRanking,
    fetchNextPage: fetchNextRanking,
    isError: isErrorRanking,
    error: rankingError
  } = useInfiniteCategoryRanking(categorySlug, itemsPerPage);

  const {
    data: detailedItemsData,
    isLoading: isLoadingDetailed,
    isError: isErrorDetailed
  } = useInfiniteItems(
    {
      category_slug: categorySlug,
      sort_by: 'vote_avg',
      sort_order: 'desc',
      per_page: 20
    },
    { enabled: !!categorySlug }
  );

  const category = useMemo(() => {
    return rankingData?.pages?.[0]?.data?.category || null;
  }, [rankingData]);

  const rankingItems = useMemo(() => {
    if (!rankingData) return [];
    return rankingData.pages.flatMap(page => page.data.ranking);
  }, [rankingData]);

  const sliderItems = useMemo(() => {
    if (!detailedItemsData) return [];
    return detailedItemsData.pages.flatMap(page => page.data).slice(0, 20);
  }, [detailedItemsData]);

  const hasRankingData = rankingItems.length > 0;
  const showNotFound = !isLoadingRanking && !category;
  const isBackgroundUpdating = isFetchingRanking && !isFetchingNextRanking && !isLoadingRanking;

  const handleItemClick = (item) => {
    navigate(`/${categorySlug}/item/${item.id}`);
  };

  const handleGoHome = () => navigate('/');

  return {
    state: {
      categorySlug,
      category,
      rankingItems,
      sliderItems,
      hasRankingData,
      isLoadingRanking,
      isFetchingRanking,
      isFetchingNextRanking,
      hasNextRanking,
      isErrorRanking,
      rankingError,
      isLoadingDetailed,
      isErrorDetailed,
      itemsPerPage,
      showNotFound,
      isBackgroundUpdating,
    },
    actions: {
      fetchNextRanking,
      handleItemClick,
      handleGoHome,
    }
  };
};