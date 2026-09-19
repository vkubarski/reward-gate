class RewardGateContent {
    constructor(element) {
        this.element = element;
        this.campaignId = element.dataset.campaignId;
        this.button = null;
        this.errorElement = null;
        this.unlocking = false;
        this.unlocked = false;
        this.ctaLabel = 'Continue';
    }

    async init() {
        try {
            const campaign = await this.loadCampaign();

            const unlocked = await this.checkStatus();

            if (unlocked) {
                this.unlocked = true;

                this.element.setAttribute(
                    'data-rg-unlocked',
                    ''
                );

                return;
            }

            this.createGate(
                campaign.presentation_settings
            );
        } catch (error) {
            this.showError(error.message);
        }
    }

    async loadCampaign() {
        if (!this.campaignId) {
            throw new Error(
                'Reward Gate campaign ID is missing.'
            );
        }

        const response = await fetch(
            `/campaigns/${this.campaignId}`
        );

        const data = await this.parseResponse(
            response,
            'Unable to load campaign.'
        );

        const campaign = data.campaign;

        if (campaign.presentation_type !== 'content') {
            throw new Error(
                'Campaign is not configured as a content gate.'
            );
        }

        if (campaign.unlock_method !== 'click') {
            throw new Error(
                'Unsupported unlock method.'
            );
        }

        return campaign;
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

    createGate(settings) {
        const container = document.createElement('div');

        container.className = 'reward-gate-content';

        this.button = document.createElement('a');

        this.button.className =
            'reward-gate-content__button';

        this.button.href =
            settings.destination_url;

        this.button.target = '_blank';

        this.button.rel = 'noopener noreferrer';

        this.ctaLabel = settings.cta_label || 'Continue';

        this.button.textContent = this.ctaLabel;
        container.appendChild(this.button);

        this.button.addEventListener(
            'click',
            (event) => {
                if (this.unlocking || this.unlocked) {
                    event.preventDefault();

                    return;
                }

                this.unlocking = true;

                this.button.setAttribute(
                    'aria-disabled',
                    'true'
                );

                this.button.textContent = 'Unlocking...';

                this.unlock();
            }
        );

        this.errorElement = document.createElement('div');

        this.errorElement.className =
            'reward-gate-content__error';

        this.errorElement.hidden = true;

        container.appendChild(this.errorElement);

        this.element.appendChild(container);
    }

    async unlock() {
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

            await this.completeUnlock(
                data.session.token
            );
        } catch (error) {
            this.unlocking = false;

            this.button.removeAttribute(
                'aria-disabled'
            );

            this.button.textContent =
                this.ctaLabel;

            this.showError(error.message);
        }
    }

    async completeUnlock(token) {
        const response = await fetch(
            '/unlock/complete',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: token
                })
            }
        );

        const data = await this.parseResponse(
            response,
            'Unable to complete unlock.'
        );

        this.unlocked = true;

        this.element.setAttribute(
            'data-rg-unlocked',
            ''
        );

        this.button.remove();
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

        const gate = new RewardGateContent(element);

        gate.init();
    }
);
