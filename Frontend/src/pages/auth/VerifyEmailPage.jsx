import React from 'react';
import FeedbackState from '../../components/ui/FeedbackState.jsx';
import Card from '../../components/ui/Card.jsx';
import FadeUp from "../../components/animations/FadeUp.jsx";

import useVerifyEmail from '../../hooks/auth/useVerifyEmail.js';

const VerifyEmailPage = () => {

  const viewConfig = useVerifyEmail();

  return (
    <div className="flex min-h-[70vh] flex-col items-center justify-center p-4">
      <FadeUp className="w-full max-w-md">
        <Card className="p-8 sm:p-10">
          <FeedbackState {...viewConfig} />
        </Card>
      </FadeUp>
    </div>
  );
};

export default VerifyEmailPage;