import { useContext } from 'react';
import { AdminCommentsContext } from '../../context/adminCommentsContext';
import { AdminItemsContext } from '../../context/adminItemsContext';
import { AdminProposalsContext } from '../../context/adminProposalsContext';
import { AdminUserDetailContext } from '../../context/adminUserDetailContext';
import { AdminUsersContext } from '../../context/adminUsersContext';

const createRequiredContextHook = (Context, hookName, providerName) => {
  return () => {
    const context = useContext(Context);

    if (!context) {
      throw new Error(`${hookName} debe utilizarse dentro de ${providerName}.`);
    }

    return context;
  };
};

export const useAdminUsersContext = createRequiredContextHook(AdminUsersContext, 'useAdminUsersContext', 'AdminUsersProvider');
export const useAdminItemsContext = createRequiredContextHook(AdminItemsContext, 'useAdminItemsContext', 'AdminItemsProvider');
export const useAdminProposalsContext = createRequiredContextHook(
  AdminProposalsContext,
  'useAdminProposalsContext',
  'AdminProposalsProvider',
);
export const useAdminCommentsContext = createRequiredContextHook(
  AdminCommentsContext,
  'useAdminCommentsContext',
  'AdminCommentsProvider',
);
export const useAdminUserDetailContext = createRequiredContextHook(
  AdminUserDetailContext,
  'useAdminUserDetailContext',
  'AdminUserDetailProvider',
);

