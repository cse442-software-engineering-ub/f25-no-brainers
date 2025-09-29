import { useEffect, useState } from "react";
import { getProducts } from "../services/api";
import { Search, Filter, MessageCircle, Bell, User } from "lucide-react";

export default function Home() {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getProducts()
      .then((data) => {
        setProducts(data);
        setLoading(false);
      })
      .catch((err) => {
        console.error(err);
        setLoading(false);
      });
  }, []);

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-blue-900 text-white flex items-center justify-between px-6 py-4">
        <h1 className="text-2xl font-bold">Dorm Mart</h1>
        <div className="flex items-center gap-2 w-1/2">
          <input
            type="text"
            placeholder="Search"
            className="w-full px-3 py-2 rounded-md text-gray-900"
          />
          <Search className="w-5 h-5 -ml-7 text-gray-500" />
        </div>
        <div className="flex gap-4">
          <Filter className="w-6 h-6 cursor-pointer" />
          <MessageCircle className="w-6 h-6 cursor-pointer" />
          <Bell className="w-6 h-6 cursor-pointer" />
          <User className="w-6 h-6 cursor-pointer" />
        </div>
      </header>

      {/* Hero Section */}
      <section className="bg-blue-800 text-white text-center py-12">
        <h2 className="text-4xl font-bold">Welcome to Dorm Mart</h2>
        <p className="mt-2 text-lg">Find what you need</p>
      </section>

      {/* Browse Listings */}
      <section className="px-8 py-10">
        <h3 className="text-2xl font-semibold mb-4">Browse Listings</h3>
        {loading ? (
          <p className="text-center text-gray-500">Loading products...</p>
        ) : products.length > 0 ? (
          <div className="grid gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            {products.map((p) => (
              <div
                key={p.id}
                className="bg-white rounded-xl shadow hover:shadow-lg p-4 flex flex-col"
              >
                <img
                  src={p.image_url}
                  alt={p.name}
                  className="h-40 w-full object-cover rounded-md"
                />
                <h4 className="mt-3 font-semibold text-lg">{p.name}</h4>
                <p className="text-gray-600 text-sm">{p.condition}</p>
                <p className="mt-2 text-blue-700 font-bold">${p.price}</p>
                <div className="flex items-center mt-2 text-sm text-gray-500">
                  <span className="mr-2">{p.seller}</span> ⭐ {p.rating}
                </div>
                <div className="flex gap-2 mt-2 flex-wrap">
                  {p.tags?.map((tag, i) => (
                    <span
                      key={i}
                      className="bg-gray-200 px-2 py-1 rounded-full text-xs"
                    >
                      {tag}
                    </span>
                  ))}
                </div>
                <button className="mt-4 bg-blue-900 text-white px-4 py-2 rounded-md hover:bg-blue-800">
                  View
                </button>
              </div>
            ))}
          </div>
        ) : (
          <p className="text-center text-gray-500">No products available yet.</p>
        )}
      </section>
    </div>
  );
}
