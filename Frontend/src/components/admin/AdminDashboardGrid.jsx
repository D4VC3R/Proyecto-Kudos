import { AdminFeatureCard } from './AdminFeatureCard';

export const AdminDashboardGrid = ({ cards }) => {
  return (
    <section className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {cards.map((card) => (
        <AdminFeatureCard
          accentClass={card.accentClass}
          icon={card.icon}
          key={card.key}
          subtitle={card.subtitle}
          title={card.title}
          to={card.to}
        />
      ))}
    </section>
  );
};
