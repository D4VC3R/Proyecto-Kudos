import React from 'react';
import { PROPOSAL_STATUS_CONFIG } from '../../lib/constants.js';
import { Filter } from 'lucide-react';
// Componentes
import SectionHeader from "../ui/SectionHeader.jsx";
import SearchFilter from "../ui/SearchFilter.jsx";
import SelectFilter from "../ui/SelectFilter.jsx";



const PROPOSAL_STATUS_OPTIONS = Object.entries(PROPOSAL_STATUS_CONFIG).map(
  ([value, config]) => ({
    label: config.label,
    value: value,
  })
);

const MyProposalsHeader = ({ meta, filters, updateParams }) => {
  return (
    <SectionHeader
      title="Mis"
      highlight="Propuestas"
      subtitle={`${meta.total || 0} propuestas enviadas en total`}
    >
      <SearchFilter
        value={filters.search || ''}
        onChange={(e) => updateParams({ search: e.target.value })}
        placeholder="Buscar propuesta..."
      />

      <SelectFilter
        icon={Filter}
        value={filters.status || ''}
        onChange={(e) => updateParams({ status: e.target.value })}
        options={PROPOSAL_STATUS_OPTIONS}
        defaultOption="Todos los estados"
      />
    </SectionHeader>
  );
};

export default MyProposalsHeader;