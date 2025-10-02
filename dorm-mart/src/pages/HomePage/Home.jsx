// src/pages/HomePage/Home.jsx
import React from "react";
import MainNav from "../../components/MainNav/MainNav";
import ItemCardNew from "../../components/ItemCardNew";
import id1 from "../../assets/icons/id1.jpg";
import id2 from "../../assets/icons/id2.jpg";
import id3 from "../../assets/icons/id3.jpg";



const Home = () => {
  const featuredItems = [
    {
      id: 1,
      title: "Freedom on My Mind- 3rd Edition",
      price: "$40",
      img: id1,
    },
    {
      id: 2,
      title: "Freedom on My Mind- 3rd Edition",
      price: "$40",
      img: id2,
    },
    {
      id: 3,
      title: "Freedom on My Mind- 3rd Edition",
      price: "$40",
      img: id3,
    },
  ];
  return (
    <div className="min-h-screen flex flex-col">
      <MainNav />

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
      <div className="w-full flex justify-start mt-8 mb-2 px-6 md:px-16">
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

      {/* Featured Items */}
      <section id="shop" className="py-16 px-6 md:px-16 bg-gray-50">
        <div className="max-w-6xl mx-auto">
          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
            {featuredItems.map((item, idx) => (
              <ItemCardNew
                key={item.id || idx}
                title={item.title}
                price={item.price}
                seller={item.seller || "Sameer J."}
                rating={item.rating || 4.7}
                location={item.location || "North Campus"}
                tags={item.tags || ["Textbook", "History"]}
                status={item.status || "JUST POSTED"}
                image={item.img}
              />
            ))}
          </div>
        </div>
      </section>
    </div>
  );
};



export default Home;
