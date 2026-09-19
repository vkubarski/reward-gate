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
        try {
            await this.startUnlock();
        } catch (error) {
            this.showError(error.message);
        }
    }

    createGate(settings) {
        this.gate = document.createElement('div');

        this.gate.className = 'reward-gate';

        this.gate.innerHTML = `
            <div class="reward-gate__dialog">
                <h2 class="reward-gate__title"></h2>

                <p class="reward-gate__message"></p>

                <div class="reward-gate__content"></div>

                <div class="reward-gate__timer">--</div>

                <div class="reward-gate__completion" hidden>
                    <div class="reward-gate__completion-title">
                        ✓ Unlocked!
                    </div>

                    <div class="reward-gate__completion-message">
                        Your content is now available.
                    </div>
                </div>

                <div class="reward-gate__expired" hidden>
                    <div class="reward-gate__expired-title">
                        Session expired
                    </div>

                    <div class="reward-gate__expired-message">
                        Your unlock session expired. Please try again.
                    </div>

                    <button
                        type="button"
                        class="reward-gate__retry"
                    >
                        Try Again
                    </button>
                </div>

                <div class="reward-gate__error" hidden></div>
            </div>
        `;

        document.body.appendChild(this.gate);

        this.titleElement = this.gate.querySelector(
            '.reward-gate__title'
        );

        this.messageElement = this.gate.querySelector(
            '.reward-gate__message'
        );

        this.contentElement = this.gate.querySelector(
            '.reward-gate__content'
        );

        this.timerElement = this.gate.querySelector(
            '.reward-gate__timer'
        );

        this.completionElement = this.gate.querySelector(
            '.reward-gate__completion'
        );

        this.expiredElement = this.gate.querySelector(
            '.reward-gate__expired'
        );

        this.retryButton = this.gate.querySelector(
            '.reward-gate__retry'
        );

        this.errorElement = this.gate.querySelector(
            '.reward-gate__error'
        );

        this.retryButton.addEventListener(
            'click',
            () => this.retryUnlock()
        );

        this.titleElement.textContent = settings.title;
        this.messageElement.textContent = settings.message;

        this.mountContent(settings.content);

        if (!settings.show_message) {
            this.messageElement.hidden = true;
        }
    }

    mountContent(content) {
        const template = document.createElement('template');

        template.innerHTML = content;

        const fragment = template.content;

        const scripts = Array.from(
            fragment.querySelectorAll('script')
        );

        scripts.forEach((script) => {
            const replacement = document.createElement('script');

            Array.from(script.attributes).forEach((attribute) => {
                replacement.setAttribute(
                    attribute.name,
                    attribute.value
                );
            });

            replacement.textContent = script.textContent;

            script.replaceWith(replacement);
        });

        this.contentElement.appendChild(fragment);
    }

    async startUnlock() {
        const campaignResponse = await fetch(
            `/campaigns/${this.campaignId}`
        );

        const campaignData = await this.parseResponse(
            campaignResponse,
            'Unable to load campaign.'
        );

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

        const unlocked = await this.checkStatus();

        if (unlocked) {
            this.unlockContent();

            return;
        }

        this.createGate(
            campaign.presentation_settings
        );

        const response = await fetch(
            `/unlock/${this.campaignId}/start`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            }
        );

        const data = await this.parseResponse(
            response,
            'Unable to start unlock.'
        );

        this.token = data.session.token;
        this.remainingSeconds =
            data.session.required_duration_seconds;

        this.updateTimer();

        this.timer = setInterval(
            () => this.tick(),
            1000
        );
    }

    async checkStatus() {
        const response = await fetch(
            `/unlock/${this.campaignId}/status`
        );

        const data = await this.parseResponse(
            response,
            'Unable to check unlock status.'
        );

        return data.unlocked === true;
    }

    async tick() {
        this.remainingSeconds--;

        if (this.remainingSeconds > 0) {
            this.updateTimer();

            return;
        }

        clearInterval(this.timer);
        this.timer = null;

        this.timerElement.textContent = 'Unlocking...';

        try {
            await this.completeUnlock();
        } catch (error) {
            if (error.message === 'Unlock session has expired.') {
                this.showExpired();

                return;
            }

            this.showError(error.message);
        }
    }

    updateTimer() {
        this.timerElement.textContent =
            `${this.remainingSeconds}s`;
    }

    async completeUnlock() {
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

        const data = await this.parseResponse(
            response,
            'Unable to complete unlock.'
        );

        this.unlockContent();
    }

    async unlockContent() {
        this.protectedContent.hidden = false;

        if (!this.gate) {
            return;
        }

        this.timerElement.hidden = true;
        this.contentElement.hidden = true;
        this.messageElement.hidden = true;
        this.completionElement.hidden = false;

        await new Promise((resolve) => {
            setTimeout(resolve, 800);
        });

        this.gate.remove();
    }

    showExpired() {
        this.timerElement.hidden = true;
        this.contentElement.hidden = true;
        this.messageElement.hidden = true;
        this.expiredElement.hidden = false;
    }

    async retryUnlock() {
        this.expiredElement.hidden = true;
        this.messageElement.hidden = false;
        this.contentElement.hidden = false;
        this.timerElement.hidden = false;

        this.remainingSeconds = 0;
        this.token = null;

        try {
            const response = await fetch(
                `/unlock/${this.campaignId}/start`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                }
            );

            const data = await this.parseResponse(
                response,
                'Unable to start unlock.'
            );

            this.token = data.session.token;
            this.remainingSeconds =
                data.session.required_duration_seconds;

            this.updateTimer();

            this.timer = setInterval(
                () => this.tick(),
                1000
            );
        } catch (error) {
            this.showError(error.message);
        }
    }

    async parseResponse(response, fallbackMessage) {
        const contentType =
            response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error(fallbackMessage);
        }

        let data;

        try {
            data = await response.json();
        } catch (error) {
            throw new Error(fallbackMessage);
        }

        if (!response.ok || !data.success) {
            throw new Error(
                data.error || fallbackMessage
            );
        }

        return data;
    }

    showError(message) {
        if (!this.errorElement) {
            console.error(message);

            return;
        }

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
