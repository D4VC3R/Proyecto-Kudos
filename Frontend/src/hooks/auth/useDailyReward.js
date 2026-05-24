import { useEffect } from 'react';
import { useMutation } from '@tanstack/react-query';
import axiosClient from '../../lib/axiosClient';
import { useSessionStore } from '../../store/useSessionStore';

export const useDailyReward = (onRewardClaimed) => {
  const { user, token, setSession } = useSessionStore();

  const mutation = useMutation({
    mutationFn: () => axiosClient.post('/profile/daily-claim'),
    onSuccess: (response) => {

      const data = response.data;
      const meta = response.meta;

      if (data.status === 'claimed') {
        const newUser = {
          ...user,
          total_kudos: data.kudos,
          login_streak_count: data.streak,
          last_login_streak_date: meta.server_date,
        };
        
        setSession({ token, user: newUser });

        if (onRewardClaimed) {
          onRewardClaimed({
            kudos: meta.kudos_awarded,
            baseKudos: meta.base_kudos,
            multiplier: meta.multiplier,
            streak: data.streak,
            isNewRecord: meta.is_new_record,
            isCritical: meta.is_critical
          });
        }
      } else if (data.status === 'already_claimed') {
        const newUser = {
          ...user,
          last_login_streak_date: meta.server_date,
        };
        setSession({ token, user: newUser });
      }
    }
  });

  useEffect(() => {
    if (!user || !user.id || !token) return;

    const today = new Date().toLocaleDateString('en-CA');
    const lastCheck = user.last_login_streak_date;

    if (!lastCheck || lastCheck !== today) {
      mutation.mutate();
    }
  }, [user?.last_login_streak_date, user?.id, token]);

  return mutation;
};
