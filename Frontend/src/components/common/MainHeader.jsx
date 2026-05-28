import React from 'react';
import { Link } from "react-router-dom";
import { Trophy } from "lucide-react";
import { NavBar } from "./NavBar.jsx";
import { UserInfo } from "../profile/UserInfo.jsx";
import { LogoutButton } from "../auth/LogoutButton.jsx";
import { HeaderLogin } from "./HeaderLogin.jsx";
import { useSessionStore } from "../../store/useSessionStore.js";

export const MainHeader = () => {
  const isAuthenticated = useSessionStore((state) => !!state.token);

  return (
    <header className="sticky top-0 z-50 w-full border-b border-border bg-surface shadow-sm">
      <div className="mx-auto flex h-[72px] w-full max-w-7xl items-center px-4 relative justify-between gap-2">

        <Link to="/" className="flex shrink-0 items-center justify-start gap-2 transition-transform hover:scale-105">
          <div className="flex h-9 w-9 md:h-10 md:w-10 flex-shrink-0 items-center justify-center rounded-xl bg-primary text-text-btn shadow-md">
            <Trophy size={20} className="md:h-6 md:w-6" />
          </div>
          <div className="hidden sm:block font-black text-xl md:text-2xl text-text-highlight tracking-tight whitespace-nowrap">
            Proyecto <span className="text-primary">Kudos</span>
          </div>
        </Link>
        <div className="order-last md:order-2 md:flex-1 md:flex md:justify-center shrink-0">
          <NavBar />
        </div>
        <div className="flex shrink-0 items-center justify-end gap-1 sm:gap-2 order-2 md:order-3 min-w-0">
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