import React from 'react';

// Pantalla de una página que no se puede mostrar: el antetítulo dice qué ha pasado, el `h1` es el único de la
// página y las acciones (`children`) siempre ofrecen una salida.
const ErrorState = ({ kicker, title, description, children }) => (
  <section className="flex min-h-state flex-col items-center justify-center gap-4 text-center">
    {kicker && <p className="text-caption font-semibold uppercase tracking-widest text-kicker">{kicker}</p>}
    <h1 className="font-display text-h1 text-ink">{title}</h1>
    {description && <p className="max-w-prose text-lg text-ink-muted">{description}</p>}
    {children && <div className="mt-4 flex flex-wrap justify-center gap-3">{children}</div>}
  </section>
);

export default ErrorState;
