import React from 'react';
import { useLocation } from 'react-router-dom';
import { adminTabs } from '../../lib/constants.js';
import Tabs from '../ui/Tabs.jsx';

const items = adminTabs.map((tab) => ({ id: tab.to, label: tab.name, icon: tab.icon, to: tab.to }));

const AdminNav = () => {
  const { pathname } = useLocation();
  const activeId = items.find(({ to }) => pathname === to || pathname.startsWith(`${to}/`))?.id;

  return <Tabs items={items} activeId={activeId} label="Secciones de administración" />;
};

export default AdminNav;
