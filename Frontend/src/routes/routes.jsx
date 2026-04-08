import { Navigate, Route, Routes } from 'react-router-dom';
import {AppLayout} from "./layouts/AppLayout.jsx";
import HomePage from "../pages/HomePage.jsx";


export const AppRoutes = () => {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route element={<HomePage />} path="/" />

      </Route>
    </Routes>
  );
};
