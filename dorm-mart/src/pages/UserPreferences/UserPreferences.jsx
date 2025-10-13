import React, { useEffect, useMemo, useRef, useState } from 'react'
import { Checkbox } from '../../components/ui/checkbox'
import { Input } from '../../components/ui/input'
import { Button } from '../../components/ui/button'
import { Badge } from '../../components/ui/badge'

export default function UserPreferences(){
  // hydrate from localStorage
  const [promoEmails, setPromoEmails] = useState(() => {
    const v = localStorage.getItem('prefs.promoEmails');
    return v ? v === 'true' : false;
  });
  const [interests, setInterests] = useState(() => {
    try { return JSON.parse(localStorage.getItem('prefs.interests') || '[]'); } catch { return []; }
  });
  const [interestQuery, setInterestQuery] = useState('');
  const [theme, setTheme] = useState(() => localStorage.getItem('prefs.theme') || 'light');
  const loadedFromBackendRef = useRef(false); // prevent POST right after GET hydration
  const BASE = process.env.REACT_APP_API_BASE || '/api';

  // persist
  useEffect(() => { localStorage.setItem('prefs.promoEmails', String(promoEmails)); }, [promoEmails]);
  useEffect(() => { localStorage.setItem('prefs.interests', JSON.stringify(interests)); }, [interests]);
  useEffect(() => {
    localStorage.setItem('prefs.theme', theme);
    // apply theme by flipping CSS variables on :root
    const root = document.documentElement;
    if (theme === 'dark') {
      root.style.setProperty('--background', '222.2 84% 4.9%');
      root.style.setProperty('--foreground', '210 40% 98%');
      root.style.setProperty('--secondary', '217.2 32.6% 17.5%');
      root.style.setProperty('--secondary-foreground', '210 40% 98%');
      root.style.setProperty('--muted', '217.2 32.6% 17.5%');
      root.style.setProperty('--muted-foreground', '215 20.2% 65.1%');
      root.style.setProperty('--accent', '217.2 32.6% 17.5%');
      root.style.setProperty('--accent-foreground', '210 40% 98%');
      root.style.setProperty('--input', '217.2 32.6% 17.5%');
    } else {
      // reset to defaults by removing overrides (theme.css provides defaults)
      root.style.removeProperty('--background');
      root.style.removeProperty('--foreground');
      root.style.removeProperty('--secondary');
      root.style.removeProperty('--secondary-foreground');
      root.style.removeProperty('--muted');
      root.style.removeProperty('--muted-foreground');
      root.style.removeProperty('--accent');
      root.style.removeProperty('--accent-foreground');
      root.style.removeProperty('--input');
    }
  }, [theme]);

  // Load from backend on mount (if authenticated). Falls back silently on error/401
  useEffect(() => {
    let cancelled = false;
    (async () => {
      try {
        const res = await fetch(`${BASE}/userPreferences.php`, { method: 'GET', credentials: 'include' });
        if (!res.ok) return; // 401/503 -> keep local values
        const json = await res.json();
        if (!json || json.ok !== true || !json.data) return;
        const { promoEmails: p = false, interests: i = [], theme: t = 'light' } = json.data;
        if (cancelled) return;
        loadedFromBackendRef.current = true; // mark hydration to skip next save
        setPromoEmails(!!p);
        setInterests(Array.isArray(i) ? i : []);
        setTheme(t === 'dark' ? 'dark' : 'light');
      } catch (e) {
        // ignore network errors; keep local
        console.warn('prefs: backend load failed', e);
      }
    })();
    return () => { cancelled = true; };
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Save to backend whenever values change (skip immediately after hydration)
  useEffect(() => {
    let cancelled = false;
    (async () => {
      if (loadedFromBackendRef.current) {
        // Skip the first cycle after hydration, then allow future saves
        loadedFromBackendRef.current = false;
        return;
      }
      try {
        const body = { promoEmails, interests, theme };
        await fetch(`${BASE}/userPreferences.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'include',
          body: JSON.stringify(body),
        });
      } catch (e) {
        if (!cancelled) console.warn('prefs: backend save failed', e);
      }
    })();
    return () => { cancelled = true; };
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [promoEmails, JSON.stringify(interests), theme]);

  function addInterest() {
    const t = interestQuery.trim();
    if (!t) return;
    setInterests((prev) => (prev.includes(t) ? prev : [...prev, t]));
    setInterestQuery('');
  }

  function removeInterest(t) {
    setInterests((prev) => prev.filter((x) => x !== t));
  }

  const hasInterests = useMemo(() => interests.length > 0, [interests]);

  return (
    <div className="flex min-h-[80vh]">
      {/* Sidebar */}
      <aside className="w-64 bg-slate-100 border-r p-4 space-y-3">
        <h2 className="text-slate-700 font-semibold mb-4">Settings</h2>
        <nav className="flex flex-col gap-3 text-slate-700">
          <button className="text-left hover:underline">Personal Information</button>
          <button className="text-left underline">User Preferences</button>
          <button className="text-left hover:underline">Security Options</button>
          <button className="text-left hover:underline">Change Password</button>
        </nav>
      </aside>

      {/* Content */}
      <main className="flex-1 p-6">
        <h1 className="text-xl font-semibold border-b pb-2">User Preferences</h1>

        <section className="mt-6 space-y-6">
          {/* Notification Settings */}
          <div>
            <h2 className="font-semibold text-slate-800">Notification Settings</h2>
            <label className="mt-2 flex items-center gap-3 text-slate-700">
              <Checkbox checked={promoEmails} onCheckedChange={(v) => setPromoEmails(!!v)} />
              <span>I would like to receive emails regarding promotional content</span>
            </label>
          </div>

          {/* My Interests */}
          <div>
            <h2 className="font-semibold text-slate-800">My Interests</h2>
            <div className="mt-3 flex items-center gap-3">
              <div className="relative">
                <Input
                  placeholder="Add interest (press Enter)"
                  className="w-56 pl-8"
                  value={interestQuery}
                  onChange={(e) => setInterestQuery(e.target.value)}
                  onKeyDown={(e) => { if (e.key === 'Enter') addInterest(); }}
                />
                <span className="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
              </div>
              <Button onClick={addInterest} variant="secondary">Add</Button>
            </div>
            <div className="mt-3 flex flex-wrap gap-2">
              {hasInterests ? interests.map((t) => (
                <Badge key={t} className="bg-slate-200 text-slate-700 flex items-center gap-2">
                  <span>{t}</span>
                  <button className="ml-1 text-slate-500 hover:text-slate-700" aria-label={`Remove ${t}`} onClick={() => removeInterest(t)}>×</button>
                </Badge>
              )) : <span className="text-sm text-slate-500">No interests added yet.</span>}
            </div>
          </div>

          {/* Seller Options */}
          <div>
            <h2 className="font-semibold text-slate-800">Seller Options</h2>
            <label className="mt-2 flex items-center gap-3 text-slate-700">
              <Checkbox />
              <span>I agree to have my email and phone number be revealed to a prospective buyer</span>
            </label>
          </div>

          {/* Theme */}
          <div>
            <h2 className="font-semibold text-slate-800">Theme</h2>
            <div className="mt-3 flex gap-2">
              <Button variant={theme === 'light' ? 'default' : 'secondary'} size="icon" onClick={() => setTheme('light')} aria-pressed={theme==='light'} title="Light theme">🌞</Button>
              <Button variant={theme === 'dark' ? 'default' : 'secondary'} size="icon" onClick={() => setTheme('dark')} aria-pressed={theme==='dark'} title="Dark theme">🌜</Button>
            </div>
          </div>
        </section>
      </main>
    </div>
  )
}
