import React from 'react';
import Ranking from '../common/Ranking';
import { Pagination } from '../common/Pagination';

export const UserRankingTable = ({ items, page, setPage, meta, itemsPerPage = 10 }) => {
  return (
    <div className="w-full">
      <Ranking
        title="Top Usuarios"
        items={items}
        page={page}
        itemsPerPage={itemsPerPage}
        type="user"
        disableScroll={true}
      />
      {meta && meta.total > itemsPerPage && (
        <div className="mt-6">
          <Pagination meta={meta} onPageChange={setPage} />
        </div>
      )}
    </div>
  );
};

