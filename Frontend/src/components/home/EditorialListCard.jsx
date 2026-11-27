import React from 'react';
import { Link } from 'react-router-dom';
import HomeItemRow from './HomeItemRow.jsx';
import Button from '../ui/Button.jsx';
import Card from '../ui/Card.jsx';
import { hasText } from '../../lib/text.js';

// Tarjeta de una lista editorial en la portada (F2-09): título, descripción y sus primeros ítems.
const EditorialListCard = ({ list }) => (
  <Card as="article" className="flex flex-col gap-3 sm:p-6">
    <h3 className="text-xl font-black text-text-highlight">
      <Link to={`/listas/${list.slug}`} className="hover:text-primary">{list.title}</Link>
    </h3>
    {hasText(list.description) && (
      <p className="line-clamp-2 text-sm font-medium text-text-normal">{list.description}</p>
    )}
    <HomeItemRow items={(list.items ?? []).map((entry) => entry.item)} />
    <Button as={Link} to={`/listas/${list.slug}`} variant="link" className="self-start">Ver lista completa</Button>
  </Card>
);

export default EditorialListCard;
