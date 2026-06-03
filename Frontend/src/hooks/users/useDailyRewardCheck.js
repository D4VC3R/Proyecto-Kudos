import { useEffect, useState, useRef } from 'react';
import { useClaimDailyReward } from './useUserMutations.js';
import { useSessionStore } from '../../store/useSessionStore.js';

const useDailyRewardCheck = () => {
  const [rewardData, setRewardData] = useState(null);

  // Extraemos solo lo necesario del store para evitar re-renderizados
  const user = useSessionStore((state) => state.user);
  const { mutate } = useClaimDailyReward();

  // Candado ligado al ID del usuario para evitar llamadas duplicadas en la misma sesión
  const checkedUserId = useRef(null);

  useEffect(() => {
    if (!user?.id) return;  // Solo si hay usuario

    const isAlreadyCheckedInSession = checkedUserId.current === user.id;
    const todayClientDate = new Date().toLocaleDateString('en-CA');
    const hasClaimedToday = user.last_login_streak_date === todayClientDate;

    if (isAlreadyCheckedInSession || hasClaimedToday) return;

    // Se bloquean las futuras ejecuciones para este ID
    checkedUserId.current = user.id;

    mutate(undefined, {
      onSuccess: ({ data, meta }) => {
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
      },
      onError: () => {
        checkedUserId.current = null;
      }
    });

  }, [user?.id, user?.last_login_streak_date, mutate]);

  const closeRewardModal = () => setRewardData(null);

  return { rewardData, closeRewardModal };
};

export default useDailyRewardCheck;