import React, {useEffect, useState, useRef} from 'react';
import {useClaimDailyReward} from '../../hooks/users/useUserMutations.js';
import {useSessionStore} from '../../store/useSessionStore.js';
import DailyRewardModal from './DailyRewardModal.jsx';

const DailyRewardChecker = () => {
  const [rewardData, setRewardData] = useState(null);
  const {user, token} = useSessionStore();
  const claimReward = useClaimDailyReward();

  // Ref para evitar ejecuciones dobles por Strict Mode
  const isChecking = useRef(false);

  useEffect(() => {
    if (!user?.id || !token || isChecking.current) return;

    const today = new Date().toLocaleDateString('en-CA');
    const lastCheck = user.last_login_streak_date;

    if (!lastCheck || lastCheck !== today) {
      isChecking.current = true;

      claimReward.mutate(undefined, {
        onSuccess: ({data, meta}) => {
          if (data?.status === 'claimed') {
            setRewardData({
              kudos: meta.kudos_awarded,
              baseKudos: meta.base_kudos,
              multiplier: meta.multiplier,
              streak: data.streak,
              isNewRecord: meta.is_new_record,
              isCritical: meta.is_critical
            });
          }
        }
      });
    }
  }, [user?.last_login_streak_date, user?.id, token]);

  return (
    <DailyRewardModal
      isOpen={!!rewardData}
      onClose={() => setRewardData(null)}
      data={rewardData}
    />
  );
};

export default DailyRewardChecker;
