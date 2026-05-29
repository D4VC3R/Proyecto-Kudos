import React from 'react';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import ScaleFadeIn from "../../components/animations/ScaleFadeIn.jsx";

import useVerifyEmail from '../../hooks/auth/useVerifyEmail.js';

const VerifyEmailPage = () => {

  const viewConfig = useVerifyEmail();

  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
      <ScaleFadeIn className="w-full max-w-md rounded-3xl bg-surface p-8 sm:p-10 shadow-2xl ring-1 ring-slate-200">
        <FeedbackState {...viewConfig} />
      </ScaleFadeIn>
    </div>
  );
};

export default VerifyEmailPage;