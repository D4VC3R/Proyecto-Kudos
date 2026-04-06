const toneClassMap = {
  neutral: 'text-slate-400',
  error: 'text-red-200',
};

export const AdminTableStateRow = ({ colSpan, message, tone = 'neutral' }) => {
  const toneClass = toneClassMap[tone] ?? toneClassMap.neutral;

  return (
    <tr>
      <td className={`px-4 py-6 text-sm ${toneClass}`} colSpan={colSpan}>
        {message}
      </td>
    </tr>
  );
};

