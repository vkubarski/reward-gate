# Reward Gate — Browser / E2E Test Procedure

This document defines the repeatable manual browser test procedure for the Reward Gate MVP.

The same procedure should be run against both the local development environment and the remote demo server.

---

## 1. Test Environment

Before starting:

* [x] Application is running and accessible
* [x] Database is available
* [x] Test campaigns exist:

  * Popup Gate + Timer
  * Content Gate + Click
* [x] Browser is using a fresh/private browsing session where required
* [x] Browser developer console is open for error checking

For remote testing, confirm the application is accessed over HTTPS.

---

## 2. Popup Gate

### 2.1 Initial Presentation

* [x] Open a page containing an active Popup Gate campaign.
* [x] Popup appears correctly.
* [x] Protected content cannot be accessed while the gate is locked.
* [x] Popup is responsive on desktop.
* [x] Popup is responsive on mobile.
* [x] Configured title is displayed correctly.
* [x] Configured message is displayed correctly.
* [x] Configured popup content is displayed correctly.
* [x] Countdown/timer is displayed correctly.
* [x] No unexpected JavaScript errors appear in the browser console.

### 2.2 Timer Unlock

* [x] Start the unlock.
* [x] Timer begins counting down.
* [x] Unlock cannot be completed before the required time.
* [x] After the required time, completion succeeds.
* [x] Protected content becomes available.
* [x] Popup closes or disappears as expected.
* [x] Page remains usable after unlocking.

### 2.3 Timer Manipulation

* [x] Start a new unlock session.
* [x] Attempt to manipulate the client-side timer.
* [x] Attempt to trigger completion before the required time.
* [x] Server rejects premature completion.
* [x] Client-side manipulation does not grant access.

### 2.4 Replay / Frequency Limit

* [x] Complete an unlock successfully.
* [x] Attempt to complete the same unlock again.
* [x] Replay is rejected.
* [x] Configure a campaign frequency limit.
* [x] Complete the campaign.
* [x] Attempt the campaign again within the frequency period.
* [x] Unlock is blocked according to the configured limit.
* [x] After the frequency period expires, the campaign can be completed again.

### 2.5 Expired / Invalid Session

* [x] Start an unlock session.
* [x] Allow the session to expire or use an invalid session.
* [x] Attempt completion.
* [x] Server rejects the completion.
* [x] Visitor receives a clear failure state.
* [x] No protected content is unlocked.

---

## 3. Content Gate

### 3.1 Initial Presentation

* [x] Open a page containing an active Content Gate campaign.
* [x] Content before the gate is visible.
* [x] Content after the gate is hidden while locked.
* [x] Continue/unlock button is displayed.
* [x] Button uses the configured CTA label.
* [x] Gate is responsive on desktop.
* [x] Gate is responsive on mobile.
* [x] No unexpected JavaScript errors appear in the browser console.

### 3.2 Click Unlock

* [x] Click the Continue/unlock button.
* [x] Unlock session is created successfully.
* [x] Server verifies the completion.
* [x] Protected content becomes visible.
* [x] Gate disappears after successful completion.
* [x] Original page content remains intact.
* [x] Configured destination opens as expected.

### 3.3 Replay / Frequency Limit

* [x] Complete the Content Gate successfully.
* [x] Reload the page.
* [x] Previously unlocked content remains available when the campaign is configured for permanent unlock.
* [x] Configure a frequency limit.
* [x] Complete the campaign.
* [x] Reload or revisit the page within the frequency period.
* [x] Gate behavior follows the configured frequency limit.
* [x] After the frequency period expires, the gate can be completed again.

### 3.4 Failure Handling

* [x] Attempt the gate with an invalid or expired session.
* [x] Server rejects the completion.
* [x] Error is displayed without breaking the page.
* [x] Gate remains locked after a failed completion.
* [x] Non-JSON or HTTP error responses do not produce raw JSON parsing errors.

---

## 4. Security / Edge Cases

### 4.1 Server Authority

* [x] Client-side state alone cannot grant an unlock.
* [x] Premature timer completion is rejected by the server.
* [x] Invalid tokens are rejected.
* [x] Expired sessions are rejected.
* [x] Completed sessions cannot be replayed.
* [x] Completion is bound to the correct visitor/session where applicable.

### 4.2 Campaign State

* [x] Inactive campaigns cannot be used.
* [x] Archived campaigns cannot be used.
* [x] Invalid campaign IDs return an appropriate error.
* [x] Incorrect presentation/unlock combinations are rejected safely.

### 4.3 JavaScript Disabled

* [x] Disable JavaScript in the browser.
* [x] Open the Content Gate demo.
* [x] Protected content remains hidden.
* [x] No protected content is exposed simply because JavaScript is disabled.

### 4.4 Browser Console

During the complete procedure:

* [x] No unexpected JavaScript errors.
* [x] No failed requests that affect the workflow.
* [x] No unexpected 404/500 responses.
* [x] No obvious security-sensitive information is exposed in the browser console.

---

## 5. Remote Demo Verification

Run the complete procedure above against the remote demo server.

### Remote Environment

* [x] HTTPS works correctly
* [x] Campaign routes work
* [x] Admin routes work
* [x] Visitor/demo routes work
* [x] Database connection works
* [x] Sessions work correctly
* [x] Cookies work correctly
* [x] Popup Gate workflow works
* [x] Content Gate workflow works
* [x] Timer verification works
* [x] Click unlock works
* [x] Replay protection works
* [x] Frequency limits work
* [x] Expired sessions are rejected
* [x] Mobile browser workflow works
* [x] No unexpected browser-console errors

---

## 6. Test Result

Record the environment and result after each complete run.

### Local

* Date:
* Browser:
* OS:
* Result:
* Issues:

### Remote Demo

* Date:
* Browser:
* Device:
* Result:
* Issues:

A failed test should be recorded with the exact reproduction steps and expected versus actual behavior.
