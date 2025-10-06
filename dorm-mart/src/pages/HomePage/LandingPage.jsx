// src/pages/HomePage/LandingPage.jsx
import React, { useEffect, useState } from "react";
import ItemCardNew from "../../components/ItemCardNew";
import id1 from "../../assets/icons/id1.jpg";
import id2 from "../../assets/icons/id2.jpg";
import id3 from "../../assets/icons/id3.jpg";


// local fallback data to show something if API is down
const FALLBACK_ITEMS = [
  { id: 1, title: "Freedom on My Mind- 3rd Edition", price: 40, img: id1, tags: ["Textbook"] },
  { id: 2, title: "Small Carpet", price: 40, img: id2, tags: ["Furniture"] },
  { id: 3, title: "Hard Drive", price: 40, img: id3, tags: ["Electronics"] },
];


const LandingPage = () => {
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(false);
  // no tag/search filters per request

  useEffect(() => {
    const controller = new AbortController();
    async function load() {
      try {
        setLoading(true);
        const BASE = process.env.REACT_APP_API_BASE || "/api"; // 
        const r = await fetch(`${BASE}/landingListings.php`, { signal: controller.signal });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const data = await r.json();
        // normalize data to our card props shape
        const normalized = (Array.isArray(data) ? data : []).map((d, i) => ({
          id: d.id ?? i,
          title: d.title ?? "Untitled",
          price: typeof d.price === 'number' ? d.price : parseFloat(`${d.price}`.replace(/[^0-9.]/g, '')) || 0,
          img: d.image ?? null,
          tags: Array.isArray(d.tags) ? d.tags : [],
        }));
        setItems(normalized.length ? normalized : FALLBACK_ITEMS);
        setError(false);
      } catch (e) {
        if (e.name === 'AbortError') return;
        console.error(e);
        setError(true);
        setItems(FALLBACK_ITEMS);
      } finally {
        setLoading(false);
      }
    }
    load();
    return () => controller.abort();
  }, []);

  // no derived tags or filtered views

  // No explicit clear-all control per request; users can toggle tags directly
  return (
    <div className="min-h-screen flex flex-col">
      {/* Hero Section */}
      <section className="flex justify-center mt-2">
        <div
          className="flex flex-col items-center justify-center text-center px-9 w-full"
          style={{
            height: "183px",
            flexShrink: 0,
            borderRadius: "25px",
            background: "#1D3445"
          }}
        >
          <h1
            style={{
              color: "#FFF",
              textAlign: "center",
              fontFamily: '"Tai Heritage Pro", serif',
              fontSize: "90px",
              fontStyle: "normal",
              fontWeight: 700,
              lineHeight: "normal"
            }}
            className="mb-2"
          >
            Welcome to Dorm Mart
          </h1>
          <p
            style={{
              width: "504px",
              height: "71px",
              flexShrink: 0,
              color: "#F4F9E9",
              textAlign: "center",
              fontFamily: '"Tai Heritage Pro", serif',
              fontSize: "40px",
              fontStyle: "normal",
              fontWeight: 700,
              lineHeight: "normal"
            }}
          >
            Find what you need
          </p>
        </div>
      </section>

      {/* Browse Listings Title */}
      <div className="w-full mt-8 px-6 md:px-16">
        <div className="flex items-end justify-between flex-wrap gap-3 mb-3">
          <h2
            style={{
              color: "#1D3445",
              fontFamily: 'Tai Heritage Pro, serif',
              fontSize: "28px",
              fontWeight: 700,
              fontStyle: "normal",
              lineHeight: "normal",
              letterSpacing: "0.5px"
            }}
            className="text-left"
          >
            Browse Listings
          </h2>
        </div>
      </div>

      {/* Featured Items */}
      <section id="shop" className="py-16 px-6 md:px-16 bg-gray-50">
        <div className="max-w-6xl mx-auto">
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
            {items.map((item, idx) => (
              <ItemCardNew
                key={item.id || idx}
                title={item.title}
                price={typeof item.price === 'number' ? `$${item.price.toFixed(2)}` : item.price}
                seller={item.seller || "Sameer J."}
                rating={item.rating || 4.7}
                location={item.location || "North Campus"}
                tags={item.tags || ["Textbook", "History"]}
                status={item.status || "JUST POSTED"}
                image={item.img || undefined}
              />
            ))}
          </div>
        </div>
      </section>
      {loading && (
        <p className="text-center text-gray-500 italic pb-8">Loading listings…</p>
      )}
      {error && (
        <p className="text-center text-red-600 italic pb-8">Couldn’t load latest listings. Showing sample items.</p>
      )}
    </div>
  );
};



export default LandingPage;
