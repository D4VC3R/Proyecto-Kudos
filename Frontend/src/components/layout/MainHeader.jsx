import React from 'react';
import { Link } from 'react-router-dom';
import NavBar from './NavBar.jsx';
import NavBarMobile from './NavBarMobile.jsx';
import HeaderLogin from './HeaderLogin.jsx';
import GlobalSearch from './GlobalSearch.jsx';
import NotificationBell from './NotificationBell.jsx';
import ThemeToggle from './ThemeToggle.jsx';
import UserMenu from './UserMenu.jsx';
import LogoMark from './LogoMark.jsx';
import { FOCUS_RING } from './headerStyles.js';
import { useSessionStore, selectIsVerified } from '../../store/useSessionStore.js';

const MainHeader = () => {
  const isAuthenticated = useSessionStore((state) => !!state.token);
  const isVerified = useSessionStore(selectIsVerified);

  return (
    <header data-site-header className="sticky top-0 z-50 w-full border-b border-line bg-canvas">
      <div className="mx-auto flex h-16 w-full max-w-content items-center gap-1 px-4 md:gap-1.5 md:px-6">
        <NavBarMobile />
        <Link
          to="/"
          aria-label="Kudos, ir a la portada"
          className={`inline-flex shrink-0 items-center gap-2 rounded-sm px-0.5 py-1 font-display text-xl font-semibold tracking-tight text-ink md:text-2xl ${FOCUS_RING}`}
        >
          <LogoMark />
          <span>Kudos</span>
        </Link>
        <NavBar />
        <div className="flex-1" />
        <div className="hidden md:block md:w-56 xl:w-72">
          <GlobalSearch />
        </div>
        <ThemeToggle className="hidden md:inline-grid" />
        {isAuthenticated ? (
          <>
            {isVerified && <NotificationBell />}
            <UserMenu />
          </>
        ) : (
          <HeaderLogin />
        )}
      </div>
    </header>
  );
};

export default MainHeader;
