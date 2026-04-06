export const AdminSectionHeader = ({
  title,
  subtitle,
  titleAs: TitleTag = 'h2',
  titleClassName = 'text-xl font-semibold',
  subtitleClassName = 'text-sm text-slate-300',
}) => {
  return (
    <header className="space-y-1">
      <TitleTag className={titleClassName}>{title}</TitleTag>
      {subtitle ? <p className={subtitleClassName}>{subtitle}</p> : null}
    </header>
  );
};

