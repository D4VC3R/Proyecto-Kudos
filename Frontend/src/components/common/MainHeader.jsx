import React from 'react';
import {Link} from "react-router-dom";
import {Trophy} from "lucide-react";
import {NavBar} from "./NavBar.jsx";
import {UserInfo} from "../profile/UserInfo.jsx";
import {LogoutButton} from "../auth/LogoutButton.jsx";
import {HeaderLogin} from "./HeaderLogin.jsx";
import {useSessionStore} from "../../store/useSessionStore.js";

export const MainHeader = () => {
  const isAuthenticated = useSessionStore((state) => !!state.token);

  return (
    <header className="sticky top-0 z-50 w-full border-b border-slate-200 bg-white shadow-sm">
      <div className="mx-auto flex h-[72px] w-full max-w-7xl items-center px-4 relative justify-between">

        <Link to="/" className="flex shrink-0 items-center justify-start gap-2 transition-transform hover:scale-105">
          <div className="flex h-9 w-9 md:h-10 md:w-10 flex-shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md">
            <Trophy size={20} className="md:h-6 md:w-6" />
          </div>
          <div className="font-black text-xl md:text-2xl text-slate-900 tracking-tight whitespace-nowrap">
            Proyecto <span className="text-blue-600">Kudos</span>
          </div>
        </Link>

        <div className="order-last md:order-2 md:flex-1 md:flex md:justify-center">
          <NavBar />
        </div>

        <div className="flex shrink-0 items-center justify-end gap-2 order-2 md:order-3 mx-2 md:mx-0 min-w-0">
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






