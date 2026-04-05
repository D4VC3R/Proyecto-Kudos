export const adminQueryKeys = {
  users: ({ page, search, banState, role, perPage, sortBy, sortDirection }) => [
    'admin-users',
    page,
    search,
    banState,
    role,
    perPage,
    sortBy,
    sortDirection,
  ],
  usersSummary: () => ['admin-users-summary'],
  userDetail: ({ userId }) => ['admin-user-detail', userId],
  items: ({ page, search, status, sortBy, sortDirection, perPage }) => [
    'admin-items',
    page,
    search,
    status,
    sortBy,
    sortDirection,
    perPage,
  ],
  proposals: ({ page, search, status, perPage }) => ['admin-proposals', page, search, status, perPage],
  comments: ({ itemId, page, perPage }) => ['admin-comments', itemId, page, perPage],
};

export const adminQueryScopes = {
  users: ['admin-users'],
  userDetail: ['admin-user-detail'],
  items: ['admin-items'],
  proposals: ['admin-proposals'],
  comments: ['admin-comments'],
};

