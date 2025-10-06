import { createBrowserRouter, createHashRouter, RouterProvider, Navigate } from 'react-router-dom'
import RootLayout from './pages/RootLayout';
import LoginPage from './pages/LoginPage';
import HomePage from './pages/HomePage';
import ItemDetailPage from './pages/PurchaseHistory/ItemDetailPage';
import PurchaseHistoryPage from './pages/PurchaseHistory/PurchaseHistoryPage';
import PurchaseHistoryLayout from './pages/PurchaseHistory/PurchaseHistoryLayout';
import UserPreferences from './pages/UserPreferences/UserPreferences';

/* Browser router with basename so URLs are clean under the app's subpath.
 Example full URL: /CSE442/2025-Fall/cse-442j/landing */

const routes = [
  // Redirect root to /app so the base URL shows the Home page
  {
    path: "/",
    element: <Navigate to="/landing" replace />,
  },
  // Keep a login route available at /login
  {
    path: "/login",
    element: <LoginPage />,
  },
  // Dedicated Landing route without the /app prefix
  {
    path: "/landing",
    element: <RootLayout />,
    children: [
      { index: true, element: <HomePage /> },
    ],
  },
  // User Preferences at clean top-level path
  {
    path: "/userpreferences",
    element: <RootLayout />,
    children: [
      { index: true, element: <UserPreferences /> },
    ],
  },
  // Support alias with space by redirecting to the clean path
  { path: "/userpreferences page", element: <Navigate to="/userpreferences" replace /> },
  // Main application lives under /app
  {
    path: "/app",
    element: <RootLayout />,
    children: [
      { index: true, element: <HomePage /> },
  { path: "settings", element: <UserPreferences /> },
      {
        path: "purchase-history",
        element: <PurchaseHistoryLayout />,
        children: [
          { index: true, element: <PurchaseHistoryPage /> },
          { path: "item-detail/:id", element: <ItemDetailPage /> },
        ],
      },
    ],
  },
];

const useHash = process.env.REACT_APP_ROUTER === 'hash';
export const router = useHash
  ? createHashRouter(routes)
  : createBrowserRouter(routes, { basename: process.env.PUBLIC_URL || '/' });

export default function App() {
  return <RouterProvider router={router} />;
}