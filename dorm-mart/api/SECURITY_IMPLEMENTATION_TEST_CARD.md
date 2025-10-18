# Task Test Card: CSRF Protection and CORS Security Implementation

## 🎯 **Task Objective**
Verify that CSRF protection and CORS security fixes are properly implemented and functioning correctly across all API endpoints.

---

# 🟢 **HAPPY PATH TESTS** - Everything Works as Expected

## ✅ **Test 1: Happy Path - Valid CSRF Token Flow**

### **🎯 Test Objective:** 
Verify that legitimate requests with valid CSRF tokens work correctly.

### **📋 Detailed Step-by-Step Instructions:**

#### **Step 1: Get CSRF Token (HAPPY PATH)**
1. **Open your web browser** (Chrome or Firefox recommended)
2. **Navigate to the test server:** `https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442j/#/login`
3. **Open Developer Tools:**
   - **Chrome:** Press `F12` or `Ctrl+Shift+I`
   - **Firefox:** Press `F12` or `Ctrl+Shift+I`
4. **Click on the "Console" tab** in the developer tools
5. **Copy and paste this EXACT code** into the console:
```javascript
fetch('/api/auth/get-csrf-token.php', {
  credentials: 'include'
})
.then(response => response.json())
.then(data => {
  console.log('CSRF Token:', data.csrf_token);
  window.csrfToken = data.csrf_token;
});
```
6. **Press Enter** to run the code
7. **Look for the output** - you should see something like:
   ```
   CSRF Token: a1b2c3d4e5f6... (64 character string)
   ```
8. **✅ SUCCESS CRITERIA:** You should see `{"ok": true, "csrf_token": "64-character-hex-string"}`

#### **Step 2: Test Login with Valid Token (HAPPY PATH)**
1. **Make sure you're still in the Console tab**
2. **Copy and paste this EXACT code** into the console:
```javascript
fetch('/api/auth/login.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  credentials: 'include',
  body: JSON.stringify({
    email: 'test@buffalo.edu',
    password: 'password123',
    csrf_token: window.csrfToken
  })
})
.then(response => response.json())
.then(data => console.log('Login Result:', data));
```
3. **Press Enter** to run the code
4. **Look for the output** - you should see:
   ```
   Login Result: {ok: true}
   ```
5. **✅ SUCCESS CRITERIA:** Should return `{"ok": true}` for valid credentials

#### **Step 3: Test Account Creation with Valid Token (HAPPY PATH)**
1. **First, get a fresh CSRF token** (copy and paste this):
```javascript
fetch('/api/auth/get-csrf-token.php', {credentials: 'include'})
.then(response => response.json())
.then(data => {
  window.csrfToken = data.csrf_token;
  console.log('New CSRF Token:', data.csrf_token);
});
```
2. **Press Enter** and wait for the new token
3. **Now test account creation** (copy and paste this):
```javascript
fetch('/api/auth/create_account.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({
    firstName: 'Test',
    lastName: 'User',
    email: 'newuser@buffalo.edu',
    password: 'password123',
    gradMonth: 5,
    gradYear: 2026,
    csrf_token: window.csrfToken
  })
})
.then(response => response.json())
.then(data => console.log('Account Creation:', data));
```
4. **Press Enter** to run the code
5. **Look for the output** - you should see:
   ```
   Account Creation: {ok: true}
   ```
6. **✅ SUCCESS CRITERIA:** Should return `{"ok": true}` for valid account creation

#### **Step 4: Test Other Protected Endpoints (HAPPY PATH)**
1. **Test logout** (copy and paste this):
```javascript
fetch('/api/auth/logout.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({csrf_token: window.csrfToken})
})
.then(response => response.json())
.then(data => console.log('Logout:', data));
```
2. **Press Enter** and look for: `Logout: {ok: true}`

3. **Test change password** (copy and paste this):
```javascript
fetch('/api/auth/change_password.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({
    currentPassword: 'oldpass',
    newPassword: 'newpass123',
    csrf_token: window.csrfToken
  })
})
.then(response => response.json())
.then(data => console.log('Password Change:', data));
```
4. **Press Enter** and look for: `Password Change: {ok: true}`

### **✅ Happy Path Success Criteria:**
- ✅ CSRF token generation works
- ✅ All endpoints accept valid CSRF tokens
- ✅ Requests complete successfully
- ✅ No 403 CSRF errors
- ✅ All responses show `{ok: true}`

---

# 🔴 **ALTERNATE PATH TESTS** - Security Should Block These

## ❌ **Test 2: Alternate Path - Invalid CSRF Token Attacks**

### **🎯 Test Objective:** 
Verify that requests without CSRF tokens or with invalid tokens are properly rejected.

### **📋 Detailed Step-by-Step Instructions:**

#### **Step 1: Test Login Without CSRF Token (ALTERNATE PATH - Should Fail)**
1. **Make sure you're still in the Console tab** of your browser developer tools
2. **Copy and paste this EXACT code** into the console:
```javascript
fetch('/api/auth/login.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({
    email: 'test@buffalo.edu',
    password: 'password123'
    // No csrf_token! ← This is intentional - we're testing security
  })
})
.then(response => {
  console.log('Status:', response.status);
  return response.json();
})
.then(data => console.log('Response:', data));
```
3. **Press Enter** to run the code
4. **Look for the output** - you should see:
   ```
   Status: 403
   Response: {ok: false, error: "CSRF token validation failed"}
   ```
5. **✅ SUCCESS CRITERIA:** Should return `403 Forbidden` with `{"ok": false, "error": "CSRF token validation failed"}`

#### **Step 2: Test Login with Invalid CSRF Token (ALTERNATE PATH - Should Fail)**
1. **Copy and paste this EXACT code** into the console:
```javascript
fetch('/api/auth/login.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({
    email: 'test@buffalo.edu',
    password: 'password123',
    csrf_token: 'fake-invalid-token-12345'  // ← This is a fake token
  })
})
.then(response => {
  console.log('Status:', response.status);
  return response.json();
})
.then(data => console.log('Response:', data));
```
2. **Press Enter** to run the code
3. **Look for the output** - you should see:
   ```
   Status: 403
   Response: {ok: false, error: "CSRF token validation failed"}
   ```
4. **✅ SUCCESS CRITERIA:** Should return `403 Forbidden` with CSRF validation error

#### **Step 3: Test All Protected Endpoints Without Tokens (ALTERNATE PATH - Should Fail)**
1. **Test account creation without token** (copy and paste this):
```javascript
fetch('/api/auth/create_account.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({
    firstName: 'Test',
    lastName: 'User',
    email: 'test@buffalo.edu'
    // No csrf_token! ← This is intentional
  })
})
.then(response => {
  console.log('Account Creation Status:', response.status);
  return response.json();
})
.then(data => console.log('Account Creation Response:', data));
```
2. **Press Enter** and look for:
   ```
   Account Creation Status: 403
   Account Creation Response: {ok: false, error: "CSRF token validation failed"}
   ```

3. **Test logout without token** (copy and paste this):
```javascript
fetch('/api/auth/logout.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({})  // ← No csrf_token!
})
.then(response => {
  console.log('Logout Status:', response.status);
  return response.json();
})
.then(data => console.log('Logout Response:', data));
```
4. **Press Enter** and look for:
   ```
   Logout Status: 403
   Logout Response: {ok: false, error: "CSRF token validation failed"}
   ```

5. **Test change password without token** (copy and paste this):
```javascript
fetch('/api/auth/change_password.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  credentials: 'include',
  body: JSON.stringify({
    currentPassword: 'oldpass',
    newPassword: 'newpass123'
    // No csrf_token! ← This is intentional
  })
})
.then(response => {
  console.log('Password Change Status:', response.status);
  return response.json();
})
.then(data => console.log('Password Change Response:', data));
```
6. **Press Enter** and look for:
   ```
   Password Change Status: 403
   Password Change Response: {ok: false, error: "CSRF token validation failed"}
   ```

### **✅ Alternate Path Success Criteria:**
- ✅ All requests without CSRF tokens return 403 Forbidden
- ✅ All requests with invalid CSRF tokens return 403 Forbidden
- ✅ Error messages indicate "CSRF token validation failed"
- ✅ No unauthorized actions are performed
- ✅ Security is working correctly

---

## 🛡️ **Test 3: CORS Security Validation (HAPPY PATH + ALTERNATE PATH)**

### **🎯 Test Objective:** 
Verify that CORS protection blocks untrusted origins while allowing trusted ones.

### **📋 Detailed Step-by-Step Instructions:**

#### **Step 1: Test Trusted Origin (HAPPY PATH - Should Work)**
1. **Make sure you're still on the legitimate application:** `https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442j/#/login`
2. **Make sure you're still in the Console tab** of your browser developer tools
3. **Copy and paste this EXACT code** into the console:
```javascript
fetch('/api/auth/get-csrf-token.php', {
  credentials: 'include'
})
.then(response => {
  console.log('CORS Status:', response.status);
  return response.json();
})
.then(data => console.log('CORS Response:', data));
```
4. **Press Enter** to run the code
5. **Look for the output** - you should see:
   ```
   CORS Status: 200
   CORS Response: {ok: true, csrf_token: "64-character-string"}
   ```
6. **✅ SUCCESS CRITERIA:** Should return `200 OK` with CSRF token

#### **Step 2: Test Untrusted Origin (ALTERNATE PATH - Should Fail)**
1. **Create a test HTML file** on your computer:
   - **Open Notepad** (or any text editor)
   - **Copy and paste this EXACT code:**
```html
<!DOCTYPE html>
<html>
<head><title>CORS Test</title></head>
<body>
  <h1>CORS Security Test</h1>
  <button onclick="testCORS()">Test CORS Protection</button>
  
  <script>
  function testCORS() {
    // This should fail because we're not from a trusted origin
    fetch('https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442j/api/auth/get-csrf-token.php', {
      credentials: 'include',
      headers: {'Origin': 'https://malicious-site.com'}
    })
    .then(response => {
      console.log('CORS Status:', response.status);
      if (response.status === 403) {
        alert('✅ CORS Protection Working! (403 Forbidden)');
      } else {
        alert('❌ CORS Protection Failed!');
      }
      return response.json();
    })
    .then(data => console.log('CORS Response:', data))
    .catch(error => console.log('CORS Error:', error));
  }
  </script>
</body>
</html>
```
2. **Save the file as `cors-test.html`** on your desktop
3. **Open the file in your browser** (double-click it)
4. **Click the "Test CORS Protection" button**
5. **Look for the alert message** - you should see:
   ```
   ✅ CORS Protection Working! (403 Forbidden)
   ```
6. **✅ SUCCESS CRITERIA:** Should return `403 Forbidden` with "Origin not allowed"

#### **Step 3: Test All Trusted Origins (HAPPY PATH - Should Work)**
1. **Test from aptitude.cse.buffalo.edu** (you're already here):
   - The test in Step 1 should have worked
2. **Test from cattle.cse.buffalo.edu** (if available):
   - Navigate to `https://cattle.cse.buffalo.edu/CSE442/2025-Fall/cse-442j/#/login`
   - Run the same CORS test from Step 1
3. **Test from localhost:3000** (if running locally):
   - Navigate to `http://localhost:3000`
   - Run the same CORS test from Step 1
4. **Test from localhost:8080** (if running locally):
   - Navigate to `http://localhost:8080`
   - Run the same CORS test from Step 1
5. **✅ SUCCESS CRITERIA:** All trusted origins should return `200 OK`

### **✅ CORS Security Success Criteria:**
- ✅ Trusted origins can access API (200 OK)
- ✅ Untrusted origins are blocked with 403 error
- ✅ CORS headers are properly set for trusted origins
- ✅ No wildcard CORS configuration found
- ✅ Security is working correctly

---

## 🔍 **Test 4: Security Headers Validation**

### **Test Objective:** 
Verify that security headers are properly set on all responses.

### **Test Steps:**

1. Open browser developer tools (F12)
2. Go to Network tab
3. Make any API request (e.g., get CSRF token)
4. Click on the request in Network tab
5. Check Response Headers for:
   - `Content-Security-Policy`
   - `X-Content-Type-Options: nosniff`
   - `X-Frame-Options: DENY`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Access-Control-Allow-Origin` (for trusted origins only)

### **Success Criteria:**
- ✅ All security headers are present
- ✅ CORS headers are set correctly for trusted origins
- ✅ No security headers are missing

---

## 📊 **Overall Success Criteria**

### **CSRF Protection:**
- ✅ Valid CSRF tokens allow requests to proceed
- ✅ Invalid/missing CSRF tokens are rejected with 403
- ✅ All state-changing endpoints are protected
- ✅ Token generation and validation work correctly

### **CORS Security:**
- ✅ Trusted origins can access the API
- ✅ Untrusted origins are blocked with 403
- ✅ No wildcard CORS configuration exists
- ✅ CORS headers are properly configured

### **Security Headers:**
- ✅ All security headers are present
- ✅ Headers are properly configured
- ✅ No security vulnerabilities in headers

### **Overall Security:**
- ✅ No linting errors in security code
- ✅ All endpoints consistently protected
- ✅ Error handling is appropriate
- ✅ Production-ready security implementation

---

## 🎯 **Test Environment Requirements**

- **Test Server:** `https://aptitude.cse.buffalo.edu/CSE442/2025-Fall/cse-442j/`
- **Production Server:** `https://cattle.cse.buffalo.edu/CSE442/2025-Fall/cse-442j/`
- **Local Development:** `http://localhost:3000` and `http://localhost:8080`
- **Browser:** Chrome/Firefox with developer tools
- **Test Data:** Valid test user credentials

---

**This comprehensive test suite validates that both CSRF protection and CORS security are properly implemented and functioning correctly!** 🛡️
