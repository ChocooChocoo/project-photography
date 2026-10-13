const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

test('pending hardcopy balance remains payable from the booking modal', () => {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/views/client/view-my-bookings.blade.php'), 'utf8');
    const start = source.indexOf('function renderBalancePaymentModal(');
    const end = source.indexOf('// Proceed button click handler', start);
    let disabled;
    const element = {html: () => {}, data: () => {}, prop: (name, value) => {if (name === 'disabled') disabled = value;}};
    vm.runInNewContext(source.slice(start, end) + '\nrenderBalancePaymentModal(data, "HC-TEST");', {
        $: () => element,
        data: {booking: {id: 1, booking_status: 'in_progress', total_amount: 1100, total_paid: 1000, remaining_balance: 100, payment_status: 'partially_paid'}, has_pending_payment: true, pending_payment_id: 2, pending_payment_amount: 100},
    });
    assert.equal(disabled, false, 'Pending payment is the checkout target, not a reason to disable checkout');
});
