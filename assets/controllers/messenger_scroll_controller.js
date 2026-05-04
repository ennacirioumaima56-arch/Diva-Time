import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['container'];

    connect() {
        this.scrollToBottom();
        
        // Watch for new content being added (Turbo Frame updates)
        this.observer = new MutationObserver(() => {
            this.scrollToBottom();
        });

        this.observer.observe(this.element, { 
            childList: true, 
            subtree: true 
        });
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    scrollToBottom() {
        this.element.scrollTop = this.element.scrollHeight;
    }
}
