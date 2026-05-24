import React from 'react';
import { useMyProposals } from '../hooks/proposals/useProposalQueries.js';
import { useDeleteProposal } from '../hooks/proposals/useProposalMutations.js';
import { MyProposalsHeader } from '../components/proposals/MyProposalsHeader.jsx';
import { MyProposalsEmpty } from '../components/proposals/MyProposalsEmpty.jsx';
import { MyProposalItemCard } from '../components/proposals/MyProposalItemCard.jsx';
import { MyProposalItemCardSkeleton } from '../components/proposals/MyProposalItemCardSkeleton.jsx'; // <-- Importamos skeleton
import { FadeUp } from "../components/animations/FadeUp.jsx";

export const MyProposalsPage = () => {
  const { data: response, isLoading, isFetching } = useMyProposals();
  const { mutate: deleteProposal, isPending: isDeleting } = useDeleteProposal();

  const proposals = response?.data || [];

  const meta = response?.meta || {};

  return (
    <div className={`flex w-full flex-col transition-opacity duration-300 ${(isFetching && !isLoading) ? 'opacity-60' : 'opacity-100'}`}>
      <FadeUp className="flex flex-col gap-6">
        <MyProposalsHeader meta={meta} />

        {isLoading ? (
          <div className="grid gap-4">
            {Array.from({ length: 4 }).map((_, i) => (
              <MyProposalItemCardSkeleton key={i} />
            ))}
          </div>
        ) : proposals.length === 0 ? (
          <MyProposalsEmpty />
        ) : (
          <div className="grid gap-4">
            {proposals.map((proposal) => (
              <MyProposalItemCard
                key={proposal.id}
                proposal={proposal}
                isDeleting={isDeleting}
                onDelete={deleteProposal}
              />
            ))}
          </div>
        )}
      </FadeUp>
    </div>
  );
};

export default MyProposalsPage;