import { createBrowserRouter, RouterProvider } from 'react-router-dom'
import RootLayout from './pages/RootLayout';
import ItemDetailPage from './pages/PurchaseHistory/ItemDetailPage';
import PurchaseHistoryPage from './pages/PurchaseHistory/PurchaseHistoryPage';
import PurchaseHistoryLayout from './pages/PurchaseHistory/PurchaseHistoryLayout';
import HomePage from "./pages/HomePage/Home.jsx"

export const router = createBrowserRouter([
  {
    path: "/",                
    element: <HomePage />,  
    children: [
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
]);

function App() {
  return <RouterProvider router={router} />
}


export default App;