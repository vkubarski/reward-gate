class RewardGatePopup {
    constructor(campaignId, protectedContent) {
        this.campaignId = campaignId;
        this.protectedContent = protectedContent;

        this.gate = null;
        this.timer = null;
        this.token = null;
        this.remainingSeconds = 0;
    }

    async init() {
        this.createGate();

        try {
            await this.startUnlock();
        } catch (error) {
            this.showError(error.message);
        }
    }

    createGate() {
        this.gate = document.createElement('div');

        this.gate.className = 'reward-gate';

        this.gate.innerHTML = `
            <div class="reward-gate__dialog">
                <h2>Unlock Content</h2>

                <p class="reward-gate__message">
                    Please wait while your content is being unlocked.
                </p>

                <div class="reward-gate__timer">--</div>

                <div class="reward-gate__error" hidden></div>
            </div>
        `;

        document.body.appendChild(this.gate);

        this.timerElement = this.gate.querySelector(
            '.reward-gate__timer'
        );

        this.errorElement = this.gate.querySelector(
            '.reward-gate__error'
        );
    }

    async startUnlock() {
    const campaignResponse = await fetch(
        `/campaigns/${this.campaignId}`
    );

    const campaignData = await campaignResponse.json();

    if (!campaignResponse.ok || !campaignData.success) {
        throw new Error(
            campaignData.error || 'Unable to load campaign.'
        );
    }

    const campaign = campaignData.campaign;

    if (campaign.presentation_type !== 'popup') {
        throw new Error(
            'Campaign is not configured as a popup gate.'
        );
    }

    if (campaign.unlock_method !== 'timer') {
        throw new Error(
            'Unsupported unlock method.'
        );
    }

    const response = await fetch(
        `/unlock/${this.campaignId}/start`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        }
    );

    const data = await response.json();

    if (!response.ok || !data.success) {
        throw new Error(
            data.error || 'Unable to start unlock.'
        );
    }

    this.token = data.session.token;
    this.remainingSeconds =
        data.session.required_duration_seconds;

    this.updateTimer();

    this.timer = setInterval(
        () => this.tick(),
        1000
    );
    }

    async tick() {
        this.remainingSeconds--;

        this.updateTimer();

        if (this.remainingSeconds > 0) {
            return;
        }

        clearInterval(this.timer);
        this.timer = null;

        await this.completeUnlock();
    }

    updateTimer() {
        this.timerElement.textContent =
            `${this.remainingSeconds}s`;
    }

    async completeUnlock() {
        try {
            const response = await fetch(
                '/unlock/complete',
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        token: this.token
                    })
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.error || 'Unable to complete unlock.'
                );
            }

            this.unlockContent();
        } catch (error) {
            this.showError(error.message);
        }
    }

    unlockContent() {
        this.protectedContent.hidden = false;

        this.gate.remove();
    }

    showError(message) {
        this.errorElement.textContent = message;
        this.errorElement.hidden = false;
    }
}

document.addEventListener(
    'DOMContentLoaded',
    () => {
        const element = document.querySelector(
            '[data-reward-gate]'
        );

        if (!element) {
            return;
        }

        const campaignId = element.dataset.campaignId;

        const protectedContent = document.querySelector(
            '#protected-content'
        );

        if (!campaignId || !protectedContent) {
            return;
        }

        const gate = new RewardGatePopup(
            campaignId,
            protectedContent
        );

        gate.init();
    }
);
