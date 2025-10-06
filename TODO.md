# TODO: Add Order Cancellation Feature

## Task Overview
Add ability for users to cancel their tool orders on myorders.php page with the following requirements:
- Only allow cancellation if more than 24 hours before start date
- Round "Days until start" to whole numbers (no decimals)
- Mark cancelled orders with 'cancelled' status (not delete)
- Send email notifications to user and company
- Display cancelled orders in separate section

## Progress Tracker

### 1. Update myorders.php
- [x] Round all day calculations using `round()` function
- [x] Add cancellation eligibility check (>24 hours before start)
- [x] Add "Cancel Order" button for eligible approved rentals
- [x] Add "Cancelled Orders" section to display cancelled orders
- [x] Display appropriate message when cancellation is not allowed

### 2. Update cancel_order.php
- [x] Modify to handle renter cancellations (not just owner cancellations)
- [x] Verify order belongs to logged-in user
- [x] Check if cancellation is allowed (>24 hours before start date)
- [x] Update availability status to 'cancelled' (not delete)
- [x] Send email notification to user
- [x] Send email notification to company
- [x] Return appropriate JSON response

### 3. Update styles.css
- [x] Add styling for cancel button (.btn-cancel class)
- [x] Add styling for cancellation warning messages
- [x] Add styling for cancelled orders section

### 4. Update script.js
- [x] Add cancelOrder() JavaScript function
- [x] Show confirmation dialog before cancellation
- [x] Handle AJAX request to cancel_order.php
- [x] Display success/error messages
- [x] Reload page after successful cancellation

### 5. Testing & Verification
- [ ] Test cancellation for orders >24 hours before start
- [ ] Test that cancellation is blocked for orders <24 hours before start
- [ ] Verify email notifications are sent correctly
- [ ] Verify cancelled orders appear in correct section
- [ ] Test with both English and Czech languages

## Notes
- Cancelled orders should be marked with status='cancelled' in database
- Email notifications go to: user + company generic email
- Cancelled orders displayed in separate section on myorders.php
