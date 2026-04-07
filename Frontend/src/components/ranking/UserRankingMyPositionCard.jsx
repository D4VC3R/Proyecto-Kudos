import { useUserRankingContext } from '../../hooks/useUserRankingContext';

export const UserRankingMyPositionCard = () => {
  const { myPosition } = useUserRankingContext();

  if (!myPosition) return null;

  return (
    <div className="rounded-xl border border-indigo-800/70 bg-indigo-950/20 p-4">
      <p className="text-sm text-indigo-200">
        Tu posición actual: <span className="font-semibold">#{myPosition.rank}</span> con{' '}
        <span className="font-semibold">{myPosition.total_kudos}</span> kudos.
      </p>
    </div>
  );
};