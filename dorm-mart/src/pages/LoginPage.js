import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import backgroundImage from '../assets/images/login-page-left-side-background.jpg';
// Client no longer inspects cookies; auth is enforced server-side on protected routes

function LoginPage() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // No client-side cookie check

  const handleLogin = async (e) => {
    e.preventDefault();
    setError(''); // Clear previous errors
    setLoading(true);

    // Validate input lengths FIRST (prevent excessively large inputs)
    if (email.length >= 50 || password.length >= 64) {
      setError('Username or password is too large');
      setLoading(false);
      return;
    }

    // Frontend validation
    if (email.trim() === '' && password.trim() === '') {
      setError('Missing required fields');
      setLoading(false);
      return;
    }

    if (email.trim() === '') {
      setError('Please input a valid email address');
      setLoading(false);
      return;
    }

    if (password.trim() === '') {
      setError('Please input a password');
      setLoading(false);
      return;
    }

    try {
      // Call backend login API
      const response = await fetch(`${process.env.REACT_APP_API_BASE}/auth/login.php`, {
        method: 'POST',
        credentials: 'include', // Important: allows cookies to be set
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: email.trim(),
          password: password
        })
      });

      const data = await response.json();

      if (data.ok) {
        // Auth token is now set server-side as httpOnly cookie
        // Navigate to the main app
        navigate('/app');
      } else {
        // Show error from backend
        setError(data.error || 'Login failed');
      }
    } catch (error) {
      // Handle network or other errors
      setError('Network error. Please try again.');
      console.error('Login error:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col md:flex-row bg-gray-50">
      {/* Left side - Background image with branding (hidden on mobile, 50% on desktop) */}
      <div className="hidden md:block md:w-1/2 relative min-h-screen">
        {/* Background image */}
        <div 
          className="absolute inset-0 bg-cover bg-center bg-no-repeat"
          style={{
            backgroundImage: `url(${backgroundImage})`
          }}
        ></div>
        
        {/* Dark overlay for better text readability */}
        <div className="absolute inset-0 bg-black bg-opacity-40"></div>
        
        {/* Branding content */}
        <div className="relative z-10 h-full flex flex-col justify-center items-center p-4 lg:p-8">
          <div className="text-center w-full px-4">
            <h1 className="text-6xl lg:text-8xl xl:text-9xl font-serif text-white mb-4 lg:mb-6 flex flex-col lg:flex-row items-center justify-center lg:space-x-6 leading-tight lg:leading-normal">
              <span>Dorm</span>
              <span>Mart</span>
            </h1>
            <h2 className="text-2xl lg:text-3xl xl:text-4xl font-light text-white opacity-90">Wastage Who?</h2>
          </div>
        </div>
      </div>
      
      {/* Right side - Login form (full width on mobile, 50% on desktop) */}
      <div className="w-full md:w-1/2 flex flex-col items-center justify-center p-4 sm:p-8 min-h-screen" style={{backgroundColor: '#364156'}}>
        {/* Mobile branding header (visible only on mobile) */}
        <div className="md:hidden mb-6 text-center">
          <h1 className="text-5xl font-serif text-white mb-2">
            Dorm Mart
          </h1>
          <h2 className="text-xl font-light text-white opacity-90">Wastage Who?</h2>
        </div>
        
        <div className="w-full max-w-md">
          <div className="p-4 sm:p-8 rounded-lg relative" style={{backgroundColor: '#3d3eb5'}}>
            {/* Torn paper effect */}
            <div className="absolute inset-0 rounded-lg" 
                 style={{
                   backgroundColor: '#3d3eb5',
                   clipPath: 'polygon(0 0, 100% 0, 100% 85%, 95% 90%, 100% 95%, 100% 100%, 0 100%)'
                 }}></div>
            
            <div className="relative z-10">
              {/* Header with dot */}
              <div className="text-center mb-6 sm:mb-8">
                <div className="w-3 h-3 bg-black rounded-full mx-auto mb-4"></div>
                <h2 className="text-3xl sm:text-4xl font-serif text-white">Log In</h2>
              </div>
              
              {/* Error message display */}
              {error && (
                <div className="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                  <p className="text-sm">{error}</p>
                </div>
              )}

              {/* Login form */}
              <form onSubmit={handleLogin} className="space-y-4 sm:space-y-6">
                {/* Email input */}
                <div>
                  <label className="block text-sm font-semibold text-gray-300 mb-2">University Email Address</label>
                  <input 
                    type="email" 
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    maxLength={50}
                    className="w-full px-4 py-3 bg-white rounded-lg border-2 border-gray-300 focus:outline-none focus:ring-4 focus:ring-blue-500/30 focus:border-blue-500 transition-all duration-200 shadow-sm hover:shadow-md focus:shadow-lg"
                    placeholder=""
                  />
                </div>
                
                {/* Password input */}
                <div>
                  <label className="block text-sm font-semibold text-gray-300 mb-2">Password</label>
                  <input 
                    type="password" 
                    value={password}
                    onChange={(e) => setPassword(e.target.value)}
                    maxLength={64}
                    className="w-full px-4 py-3 bg-white rounded-lg border-2 border-gray-300 focus:outline-none focus:ring-4 focus:ring-blue-500/30 focus:border-blue-500 transition-all duration-200 shadow-sm hover:shadow-md focus:shadow-lg"
                    placeholder=""
                  />
                </div>
                
                {/* Login button with arrow */}
                <button 
                  type="submit"
                  disabled={loading}
                  className="w-full sm:w-1/2 md:w-1/3 bg-blue-500 hover:bg-blue-600 disabled:bg-blue-400 disabled:cursor-not-allowed text-white py-2 sm:py-3 rounded-lg flex items-center justify-center space-x-2 transition-all duration-200 hover:scale-105 hover:shadow-lg font-medium mx-auto disabled:hover:scale-100"
                >
                  <span>{loading ? 'Logging in...' : 'Login'}</span>
                  {!loading && (
                    <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clipRule="evenodd" />
                    </svg>
                  )}
                </button>
              </form>
              
              {/* Links */}
              <div className="mt-4 sm:mt-6 text-center">
                <div className="flex items-center justify-center space-x-2 text-sm sm:text-base text-white">
                  <a 
                    href="#" 
                    onClick={(e) => { e.preventDefault(); navigate('/create-account'); }}
                    className="hover:underline hover:text-blue-400 transition-colors duration-200"
                  >
                    create account
                  </a>
                  <span className="w-1 h-1 bg-black rounded-full"></span>
                  <a 
                    href="#" 
                    onClick={(e) => { e.preventDefault(); navigate('/forgot-password'); }}
                    className="hover:underline hover:text-blue-400 transition-colors duration-200"
                  >
                    forgot password?
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default LoginPage;

