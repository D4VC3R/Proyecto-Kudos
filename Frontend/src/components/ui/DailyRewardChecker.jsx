import React from 'react';
import  useDailyRewardCheck  from '../../hooks/users/useDailyRewardCheck.js';
import DailyRewardModal from './DailyRewardModal.jsx';

const DailyRewardChecker = () => {
  const { rewardData, closeRewardModal } = useDailyRewardCheck();

  return (
    <DailyRewardModal
      isOpen={!!rewardData}
      onClose={closeRewardModal}
      data={rewardData}
    />
  );
};

export default DailyRewardChecker;