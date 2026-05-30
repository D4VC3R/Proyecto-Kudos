import React from 'react';
import { Link } from "react-router-dom";
import NavBar from "./NavBar.jsx";
import UserInfo from "../profile/UserInfo.jsx";
import LogoutButton from "../auth/LogoutButton.jsx";
import HeaderLogin from "./HeaderLogin.jsx";
import { useSessionStore } from "../../store/useSessionStore.js";
import LogoKudos from "./LogoKudos.jsx";

const MainHeader = () => {
  const isAuthenticated = useSessionStore((state) => !!state.token);

  return (
    <header className="sticky top-0 z-50 w-full border-b border-border bg-surface shadow-sm">
      <div className="mx-auto flex h-[80px] w-full max-w-7xl items-center px-4 justify-between gap-3">
        <Link
          to="/"
          className="flex shrink-0 items-center justify-start gap-3 transition-transform hover:scale-105 outline-none focus-visible:ring-2 focus-visible:ring-primary rounded-lg"
        >
          <LogoKudos className="h-auto w-12 md:w-14 lg:w-16" />

          <div className="hidden lg:block font-black text-2xl text-text-highlight tracking-tight whitespace-nowrap">
            Proyecto <span className="text-primary">Kudos</span>
          </div>
        </Link>

        <div className="order-last md:order-2 md:flex-1 flex justify-center shrink-0">
          <NavBar />
        </div>

        <div className="flex shrink-0 items-center justify-end gap-2 order-2 md:order-3 min-w-0">
          {isAuthenticated ? (
            <>
              <UserInfo />
              <LogoutButton />
            </>
          ) : (
            <HeaderLogin />
          )}
        </div>
      </div>
    </header>
  );
}

export default MainHeader;