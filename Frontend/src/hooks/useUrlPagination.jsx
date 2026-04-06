import {useCallback} from 'react';
import {useSearchParams} from 'react-router-dom';

export const useUrlPagination = () => {
  const [searchParams, setSearchParams] = useSearchParams();

  const pageParam = searchParams.get('page');
  const parsedPage = Number(pageParam ?? 1);
  const page = Number.isInteger(parsedPage) && parsedPage > 0 ? parsedPage : 1;

  const updatePage = useCallback(
    (nextPage, {replace = false} = {}) => {
      const safePage = Math.max(1, Number(nextPage) || 1);

      setSearchParams(
        (prev) => {
          const params = new URLSearchParams(prev);
          if (safePage === 1) {
            params.delete('page');
          } else {
            params.set('page', String(safePage));
          }
          return params;
        },
        {replace},
      );
    },
    [setSearchParams],
  );

  return {
    page,
    pageParam,
    parsedPage,
    updatePage,
  };
};