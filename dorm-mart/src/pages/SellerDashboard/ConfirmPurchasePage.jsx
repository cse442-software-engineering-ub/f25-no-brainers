import { useContext, useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { ChatContext } from '../../context/ChatContext';

// Keep API base resolution consistent with other dashboard pages
const API_BASE = (process.env.REACT_APP_API_BASE || 'api').replace(/\/?$/, '');

function ConfirmPurchasePage() {
  const location = useLocation();
  const navigate = useNavigate();
  const { conversations } = useContext(ChatContext) || { conversations: [] };
  const navState = location.state && typeof location.state === 'object' ? location.state : null;

  const [listings, setListings] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [formError, setFormError] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [selectedListingId, setSelectedListingId] = useState(navState?.productId ? String(navState.productId) : '');
  const [selectedConversationId, setSelectedConversationId] = useState(navState?.convId ? String(navState.convId) : '');

  // Meeting date/time + description (match SchedulePurchase form UX)
  const [meetingDate, setMeetingDate] = useState('');
  const [meetingHour, setMeetingHour] = useState('');
  const [meetingMinute, setMeetingMinute] = useState('');
  const [meetingAmPm, setMeetingAmPm] = useState('');
  const [dateTimeError, setDateTimeError] = useState('');
  const [description, setDescription] = useState('');

  // Gating: ensure there is an ACCEPTED scheduled purchase first (buyer perspective)
  const [hasAcceptedScheduledPurchase, setHasAcceptedScheduledPurchase] = useState(false);

  useEffect(() => {
    const abort = new AbortController();
    async function loadListings() {
      setLoading(true);
      setError('');
      try {
        const res = await fetch(`${API_BASE}/seller-dashboard/manage_seller_listings.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          credentials: 'include',
          signal: abort.signal,
          body: JSON.stringify({}),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        if (!data.success) throw new Error(data.error || 'Failed to load listings');
        const listingsData = Array.isArray(data.data) ? data.data : [];
        setListings(listingsData);
      } catch (e) {
        if (e.name !== 'AbortError') setError('Unable to load your listings right now.');
      } finally {
        setLoading(false);
      }
    }
    loadListings();
    return () => abort.abort();
  }, []);

  // Convert 12-hour format to 24-hour format
  const convertTo24Hour = (hour, amPm) => {
    const hourNum = parseInt(hour);
    if (amPm === 'PM' && hourNum !== 12) return hourNum + 12;
    if (amPm === 'AM' && hourNum === 12) return 0;
    return hourNum;
  };

  // Get current Eastern Time as a Date object
  const getEasternTime = () => {
    const now = new Date();
    const easternFormatter = new Intl.DateTimeFormat('en-US', {
      timeZone: 'America/New_York',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
      hour12: false
    });
    const parts = easternFormatter.formatToParts(now);
    const year = parseInt(parts.find(p => p.type === 'year').value);
    const month = parseInt(parts.find(p => p.type === 'month').value) - 1;
    const day = parseInt(parts.find(p => p.type === 'day').value);
    const hour = parseInt(parts.find(p => p.type === 'hour').value);
    const minute = parseInt(parts.find(p => p.type === 'minute').value);
    const second = parseInt(parts.find(p => p.type === 'second').value);
    return new Date(year, month, day, hour, minute, second);
  };

  const getTodayDate = () => {
    const easternNow = getEasternTime();
    const year = easternNow.getFullYear();
    const month = String(easternNow.getMonth() + 1).padStart(2, '0');
    const day = String(easternNow.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  const getMaxDate = () => {
    const easternNow = getEasternTime();
    const threeMonths = new Date(easternNow);
    threeMonths.setMonth(threeMonths.getMonth() + 3);
    const year = threeMonths.getFullYear();
    const month = String(threeMonths.getMonth() + 1).padStart(2, '0');
    const day = String(threeMonths.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  const validateDateTime = () => {
    setDateTimeError('');
    if (!meetingDate || !meetingHour || !meetingMinute || !meetingAmPm) {
      setDateTimeError('Please complete all date and time fields.');
      return false;
    }
    const easternNow = getEasternTime();
    const easternYear = easternNow.getFullYear();
    const easternMonth = easternNow.getMonth() + 1;
    const easternDay = easternNow.getDate();
    const easternHour = easternNow.getHours();
    const easternMinute = easternNow.getMinutes();

    const [year, month, day] = meetingDate.split('-').map(Number);
    const selectedHour24 = convertTo24Hour(meetingHour, meetingAmPm);
    const selectedMinute = parseInt(meetingMinute);

    if (year < easternYear || (year === easternYear && month < easternMonth) || (year === easternYear && month === easternMonth && day < easternDay)) {
      setDateTimeError('Meeting date cannot be in the past.');
      return false;
    }
    if (year === easternYear && month === easternMonth && day === easternDay) {
      if (selectedHour24 < easternHour || (selectedHour24 === easternHour && selectedMinute <= easternMinute)) {
        setDateTimeError('Meeting time must be in the future.');
        return false;
      }
    }
    const selectedDateTime = new Date(year, month - 1, day, selectedHour24, selectedMinute, 0);
    const threeMonthsFromNow = new Date(easternNow);
    threeMonthsFromNow.setMonth(threeMonthsFromNow.getMonth() + 3);
    if (selectedDateTime > threeMonthsFromNow) {
      setDateTimeError('Meeting date cannot be more than 3 months in advance.');
      return false;
    }
    return true;
  };

  const combineDateTime = () => {
    if (!meetingDate || !meetingHour || !meetingMinute || !meetingAmPm) return null;
    const hour24 = convertTo24Hour(meetingHour, meetingAmPm);
    const [year, month, day] = meetingDate.split('-').map(Number);
    const dateTimeString = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}T${String(hour24).padStart(2, '0')}:${meetingMinute}:00`;
    const easternTimeOptions = { timeZone: 'America/New_York', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hour12: false };
    const checkUtcDate = (utcOffset) => {
      const utcDate = new Date(`${dateTimeString}${utcOffset}`);
      const utcAsEastern = utcDate.toLocaleString('en-US', easternTimeOptions);
      const parts = utcAsEastern.match(/(\d+)\/(\d+)\/(\d+),?\s+(\d+):(\d+)/);
      if (parts) {
        const [, partMonth, partDay, partYear, partHour, partMinute] = parts.map(Number);
        if (partYear === year && partMonth === month && partDay === day && partHour === hour24 && partMinute === parseInt(meetingMinute)) {
          return utcDate.toISOString();
        }
      }
      return null;
    };
    return checkUtcDate('-05:00') || checkUtcDate('-04:00') || new Date(`${dateTimeString}-05:00`).toISOString();
  };

  // Verify accepted schedule exists for this (buyer) context
  useEffect(() => {
    const controller = new AbortController();
    async function checkAccepted() {
      setHasAcceptedScheduledPurchase(false);
      try {
        const res = await fetch(`${API_BASE}/scheduled-purchases/list_buyer.php`, {
          method: 'GET',
          headers: { 'Accept': 'application/json' },
          credentials: 'include',
          signal: controller.signal,
        });
        if (!res.ok) return;
        const payload = await res.json();
        if (!payload?.success || !Array.isArray(payload?.data)) return;
        const convId = navState?.convId ? Number(navState.convId) : (selectedConversationId ? Number(selectedConversationId) : null);
        const prodId = navState?.productId ? Number(navState.productId) : (selectedListingId ? Number(selectedListingId) : null);
        const matches = payload.data.filter((req) => {
          const status = String(req.status || '').toLowerCase();
          if (status !== 'accepted') return false;
          const reqProductId = req.product_id ?? req.inventory_product_id ?? req.listing_id ?? null;
          const reqConvId = req.conversation_id ?? req.conv_id ?? null;
          const productMatches = reqProductId != null && prodId != null && Number(reqProductId) === Number(prodId);
          const convMatches = reqConvId == null || (convId != null && Number(reqConvId) === Number(convId));
          return productMatches && convMatches;
        });
        setHasAcceptedScheduledPurchase(matches.length > 0);
      } catch (_) {
        setHasAcceptedScheduledPurchase(false);
      }
    }
    checkAccepted();
    return () => controller.abort();
  }, [API_BASE, navState, selectedConversationId, selectedListingId]);

  const listingOptions = useMemo(() => {
    if (!Array.isArray(listings)) return [];
    return listings.map((l) => ({
      id: l.id,
      title: l.title,
      price: l.price,
      status: l.status,
    }));
  }, [listings]);

  const conversationOptions = useMemo(() => {
    if (!Array.isArray(conversations)) return [];
    return conversations.map((c) => ({
      convId: c.conv_id,
      receiverId: c.receiverId,
      label: c.receiverName || `User #${c.receiverId}`,
    }));
  }, [conversations]);

  async function handleSubmit(e) {
    e.preventDefault();
    setFormError('');
    setDateTimeError('');

    if (!hasAcceptedScheduledPurchase) {
      setFormError('You can only confirm after the seller accepts a Scheduled Purchase.');
      return;
    }

    if (!validateDateTime()) {
      return;
    }

    // If prefilled from navState, use those values
    const finalListingId = navState?.productId ? String(navState.productId) : selectedListingId;
    const finalConversationId = navState?.convId ? String(navState.convId) : selectedConversationId;

    if (!finalListingId || !finalConversationId) {
      setFormError('Please select a listing and a buyer.');
      return;
    }

    setIsSubmitting(true);
    try {
      const res = await fetch(`${API_BASE}/confirmPurchase/confirmPurchase.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'include',
        body: JSON.stringify({
          inventory_product_id: Number(finalListingId),
          conversation_id: Number(finalConversationId),
          meeting_at: combineDateTime(),
          description: description.trim() || null,
        }),
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      // Best-effort parse; backend may not be ready yet
      await res.json().catch(() => ({}));

      // Return to chat, focusing the conversation when available
      if (navState?.convId) {
        navigate(`/app/chat?conv=${navState.convId}`);
      } else {
        navigate('/app/chat');
      }
    } catch (err) {
      setFormError(err.message || 'Could not confirm the purchase. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-gray-100">Confirm Purchase</h1>

        {/* Warning box */}
        <div className="mt-4 p-4 rounded-lg border-2 border-red-400 bg-red-50 dark:bg-red-900/20 dark:border-red-700">
          <p className="text-sm text-red-700 dark:text-red-200 font-semibold">
            Submitting this form confirms the reciept of the listed item and confirms that all transactions related to the item are complete. DO NOT fill this out if the transactions are not complete. This form must only be completed after the Schedule Purchase form.
          </p>
        </div>

        {loading ? (
          <p className="mt-6 text-sm text-gray-500 dark:text-gray-400">Loading…</p>
        ) : error ? (
          <p className="mt-6 text-sm text-red-600 dark:text-red-400">{error}</p>
        ) : (
          <form onSubmit={handleSubmit} className="mt-6 space-y-6">
            {/* Listing selection */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Select Listing</label>
              <select
                value={selectedListingId}
                onChange={(e) => setSelectedListingId(e.target.value)}
                disabled={!!navState?.productId}
                className="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-70"
              >
                <option value="">&lt;Select Listing&gt;</option>
                {listingOptions.map((l) => (
                  <option key={l.id} value={String(l.id)}>
                    {l.title || `Listing #${l.id}`}
                  </option>
                ))}
              </select>
              {navState?.productId && (
                <p className="mt-1 text-xs text-blue-600 dark:text-blue-400">Prefilled from chat</p>
              )}
            </div>

            {/* Buyer (conversation) selection */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Select Buyer</label>
              <select
                value={selectedConversationId}
                onChange={(e) => setSelectedConversationId(e.target.value)}
                disabled={!!navState?.convId}
                className="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-70"
              >
                <option value="">&lt;Select Buyer&gt;</option>
                {conversationOptions.map((c) => (
                  <option key={c.convId} value={String(c.convId)}>
                    {c.label} (Conversation #{c.convId})
                  </option>
                ))}
              </select>
              {navState?.convId && (
                <p className="mt-1 text-xs text-blue-600 dark:text-blue-400">Prefilled from chat</p>
              )}
            </div>

            {/* Meeting Date & Time - replicate schedule purchase styling */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Meeting Date &amp; Time</label>
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                  <label className="block text-xs text-gray-600 dark:text-gray-400 mb-1">Date</label>
                  <input
                    type="date"
                    value={meetingDate}
                    onChange={(e) => { setMeetingDate(e.target.value); setDateTimeError(''); }}
                    min={getTodayDate()}
                    max={getMaxDate()}
                    className="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <div>
                  <label className="block text-xs text-gray-600 dark:text-gray-400 mb-1">Hour</label>
                  <select
                    value={meetingHour}
                    onChange={(e) => { setMeetingHour(e.target.value); setDateTimeError(''); }}
                    className="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">--</option>
                    {Array.from({ length: 12 }, (_, i) => i + 1).map((hour) => (
                      <option key={hour} value={String(hour)}>{hour}</option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block text-xs text-gray-600 dark:text-gray-400 mb-1">Minute</label>
                  <select
                    value={meetingMinute}
                    onChange={(e) => { setMeetingMinute(e.target.value); setDateTimeError(''); }}
                    className="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">--</option>
                    {Array.from({ length: 12 }, (_, i) => i * 5).map((m) => {
                      const ms = String(m).padStart(2, '0');
                      return <option key={ms} value={ms}>{ms}</option>;
                    })}
                  </select>
                </div>
                <div>
                  <label className="block text-xs text-gray-600 dark:text-gray-400 mb-1">AM/PM</label>
                  <select
                    value={meetingAmPm}
                    onChange={(e) => { setMeetingAmPm(e.target.value); setDateTimeError(''); }}
                    className="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="">--</option>
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                  </select>
                </div>
              </div>
              {dateTimeError && (
                <p className="mt-2 text-sm text-red-600 dark:text-red-400">{dateTimeError}</p>
              )}
            </div>

            {/* Description */}
            <div>
              <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200 mb-2">Description (Optional)</label>
              <textarea
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                rows={4}
                maxLength={1000}
                placeholder="Add any additional details about the meeting..."
                className="w-full bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y"
              />
              <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{description.length}/1000 characters</p>
            </div>

            {!hasAcceptedScheduledPurchase && (
              <div className="text-sm text-yellow-700 dark:text-yellow-300">
                You can submit this only after the seller has accepted a Scheduled Purchase for this item.
              </div>
            )}

            {formError && (
              <p className="text-sm text-red-600 dark:text-red-400">{formError}</p>
            )}

            <div className="pt-2">
              <button
                type="submit"
                disabled={isSubmitting || !hasAcceptedScheduledPurchase}
                className={`px-4 py-2 rounded-lg text-sm font-medium text-white ${isSubmitting || !hasAcceptedScheduledPurchase ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700'}`}
              >
                {isSubmitting ? 'Submitting…' : 'Confirm Purchase'}
              </button>
              <button
                type="button"
                onClick={() => navigate(-1)}
                className="ml-3 px-4 py-2 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                Cancel
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  );
}

export default ConfirmPurchasePage;
