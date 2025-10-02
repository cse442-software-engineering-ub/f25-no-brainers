
import React from "react";
import puppy from "../assets/icons/puppy.jpg";

export default function ItemCardNew({
  title = "Freedom on My Mind - 3rd Edition",
  price = "$40",
  seller = "Sameer J.",
  rating = 4.7,
  location = "North Campus",
  tags = ["Textbook", "History"],
  status = "JUST POSTED",
  image = puppy,
  onView = () => {},
}) {
  return (
    <article className="bg-white rounded-2xl shadow-md p-6 w-full max-w-full flex flex-col gap-4 items-start">
      <img
        src={image}
        alt={title}
        className="w-full h-48 object-cover rounded-xl border mb-3"
      />
      <div className="flex-1 min-w-0 w-full">
        <div className="flex items-start justify-between gap-4">
          <div className="min-w-0">
            <h3 className="text-xl font-bold text-gray-900 truncate">{title}</h3>
            <p className="text-xs text-gray-500 mt-1">Used</p>
          </div>
          <div className="text-indigo-900 font-bold text-2xl whitespace-nowrap">{price}</div>
        </div>
        <div className="flex items-center gap-2 mt-2 text-sm text-gray-700">
          <span className="font-semibold text-gray-800">{seller}</span>
          <span className="text-yellow-400">★</span>
          <span className="font-medium">{rating}</span>
        </div>
        <div className="flex items-center gap-2 mt-1 text-gray-600 text-sm">
          <span>📍</span>
          <span>{location}</span>
        </div>
        <div className="flex flex-wrap gap-2 mt-2">
          {Array.isArray(tags) && tags.map((tag) => (
            <span key={tag} className="bg-slate-100 text-slate-600 text-xs px-3 py-1 rounded-full">{tag}</span>
          ))}
        </div>
        <div className="flex items-center justify-between mt-4">
          <span className="text-indigo-700 text-xs font-semibold">{status}</span>
          <button
            onClick={onView}
            className="bg-indigo-900 text-white rounded-lg px-4 py-2 font-semibold text-sm shadow hover:bg-indigo-800 transition"
            aria-label={`View ${title}`}
          >
            View
          </button>
        </div>
      </div>
    </article>
  );
}
