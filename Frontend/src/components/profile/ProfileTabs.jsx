import React from 'react';
import Tabs from '../ui/Tabs.jsx';

const ProfileTabs = ({ tabs, activeTab, setActiveTab }) => (
  <Tabs
    items={tabs.map((tab) => ({ id: tab.id, label: tab.name, icon: tab.icon }))}
    activeId={activeTab}
    onChange={setActiveTab}
    label="Secciones del perfil"
    idPrefix="profile"
    className="mb-6"
  />
);

export default ProfileTabs;
