import React from 'react';
import { motion } from 'framer-motion';
import { Loader2 } from 'lucide-react';
import { useMyProposals } from '../../hooks/proposals/useProposalQueries';
import { useDeleteProposal } from '../../hooks/proposals/useProposalMutations';
import { MyProposalsHeader } from '../../components/proposals/MyProposalsHeader';
import { MyProposalsEmpty } from '../../components/proposals/MyProposalsEmpty';
import { MyProposalItemCard } from '../../components/proposals/MyProposalItemCard';

export const MyProposalsPage = () => {
  const { data: response, isLoading } = useMyProposals();
  const { mutate: deleteProposal, isPending: isDeleting } = useDeleteProposal();

  if (isLoading) {
    return (
      <div className="flex justify-center items-center py-20 min-h-[50vh]">
        <Loader2 className="animate-spin text-blue-500" size={48} />
      </div>
    );
  }

  const proposals = response?.data || [];
  const meta = response?.meta || {};

  return (
    <div className="flex w-full flex-col">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4 }}
        className="flex flex-col gap-6"
      >
        <MyProposalsHeader meta={meta} />

        {proposals.length === 0 ? (
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
      </motion.div>
    </div>
  );
};

export default MyProposalsPage;


