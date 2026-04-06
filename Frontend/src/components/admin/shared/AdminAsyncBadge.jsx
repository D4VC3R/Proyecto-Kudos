export const AdminAsyncBadge = ({ visible, text = 'Actualizando resultados...' }) => {
  return (
    <p className={`text-sm text-slate-400 transition-opacity duration-150 ${visible ? 'opacity-100' : 'opacity-0'}`} role="status">
      {text}
    </p>
  );
};

