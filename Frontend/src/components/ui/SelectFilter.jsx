import React from 'react';
import Select from './Select.jsx';

const SelectFilter = ({value, onChange, options, defaultOption = "Todos", icon, label}) => (
  <Select
    hideLabel
    label={label ?? defaultOption}
    placeholder={defaultOption}
    options={options}
    icon={icon}
    value={value}
    onChange={onChange}
    selectClassName="w-auto"
  />
);

export default SelectFilter;
