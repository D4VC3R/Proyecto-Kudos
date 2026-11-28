import React from 'react';
import { PROPOSAL_STATUS_CONFIG } from '../../lib/constants.js';
import { Filter, Search } from 'lucide-react';
// Componentes
import SectionHead from "../ui/SectionHead.jsx";
import Input from "../ui/Input.jsx";
import SelectFilter from "../ui/SelectFilter.jsx";

const PROPOSAL_STATUS_OPTIONS = Object.entries(PROPOSAL_STATUS_CONFIG).map(
  ([value, config]) => ({
    label: config.label,
    value: value,
  })
);

const MyProposalsHeader = ({ meta, filters, updateParams }) => {
  return (
    <div className="flex flex-col gap-4">
      <SectionHead title="Mis Propuestas" />
      <p className="text-small text-ink-muted">{meta.total || 0} propuestas enviadas en total</p>

      <div className="flex flex-wrap items-center gap-3">
        <SelectFilter
          label="Estado"
          icon={Filter}
          value={filters.status || ''}
          onChange={(e) => updateParams({ status: e.target.value })}
          options={PROPOSAL_STATUS_OPTIONS}
          defaultOption="Todos los estados"
        />

        <Input
          hideLabel
          label="Buscar propuesta"
          type="search"
          icon={Search}
          placeholder="Buscar propuesta..."
          value={filters.search || ''}
          onChange={(e) => updateParams({ search: e.target.value })}
          className="w-full sm:w-64"
        />
      </div>
    </div>
  );
};

export default MyProposalsHeader;
