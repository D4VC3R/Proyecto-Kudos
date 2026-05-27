import React from 'react';
import { useParams, Navigate } from 'react-router-dom';
import { Loader2 } from 'lucide-react';
import { NewProposalForm } from '../components/proposals/NewProposalForm.jsx';
import { NewProposalHeader } from '../components/proposals/NewProposalHeader.jsx';
import { NewProposalInfoBanner } from '../components/proposals/NewProposalInfoBanner.jsx';
import { useCategoryDetail } from '../hooks/categories/useCategoryQueries.js';
import {FadeUp} from "../components/animations/FadeUp.jsx";

export const NewProposalPage = () => {
  const { categorySlug } = useParams();
  const { data: category, isLoading, isError } = useCategoryDetail(categorySlug);

  if (isLoading) {
    return (
      <div className="flex justify-center py-20">
        <Loader2 className="animate-spin text-blue-500" size={48} />
      </div>
    );
  }

  if (isError || !category) {
    return <Navigate to="/" replace />;
  }

  return (
    <div className="mx-auto flex w-full max-w-3xl flex-col items-center py-8 px-4 sm:px-6 lg:px-8">
      <FadeUp className="w-full rounded-3xl bg-surface p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200">
        <NewProposalHeader categoryName={category.name} />

        <div className="w-full">
          <NewProposalForm category={category} />
        </div>

        <NewProposalInfoBanner />
      </FadeUp>
    </div>
  );
};

export default NewProposalPage;
