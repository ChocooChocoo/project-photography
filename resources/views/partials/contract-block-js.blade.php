{{-- Renders the contract status banner shown on top of the booking detail modal for long-term bookings. --}}
<script>
    window.renderContractBlock = function (contract, role) {
        if (!contract) return '';

        const mine = role === 'client' ? contract.client_signed : contract.provider_signed;
        const other = role === 'client' ? contract.provider_signed : contract.client_signed;

        let text;
        let tone;
        if (contract.fully_signed) {
            text = 'Contract signed by both parties.';
            tone = 'success';
        } else if (!mine) {
            text = 'This long-term booking needs your signature. It is confirmed only after both parties sign.';
            tone = 'warning';
        } else {
            text = other ? '' : 'You signed. Waiting for the other party to sign.';
            tone = 'info';
        }

        const label = contract.fully_signed || mine ? 'View contract' : 'Sign contract';

        return `
            <div class="alert alert-${tone} d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <span><i class="ti ti-file-text me-1"></i> ${text}</span>
                <a href="${contract.url}" class="btn btn-sm btn-${mine || contract.fully_signed ? 'default' : 'primary'}">${label}</a>
            </div>`;
    };
</script>
