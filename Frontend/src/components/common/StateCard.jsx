import { getApiErrorMessage } from '../../lib/apiErrorMap';

const toneClassMap = {
  neutral: 'border-slate-800 bg-slate-900 text-slate-400',
  error: 'border-red-800/60 bg-red-950/30 text-red-200',
};

export const StateCard = ({ message, error, tone = 'neutral' }) => {
  const resolvedMessage = message ?? (error ? getApiErrorMessage(error) : 'Sin informacion disponible.');
  const resolvedTone = toneClassMap[tone] ?? toneClassMap.neutral;

  return (
    <div className={`rounded-xl border p-5 text-sm ${resolvedTone}`}>
      <p>{resolvedMessage}</p>
    </div>
  );
};

