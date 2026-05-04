import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [ "password" ];

    toggle(event) {
        event.preventDefault();
        const icon = event.currentTarget.querySelector('i');
        
        this.passwordTargets.forEach(target => {
            if (target.type === "password") {
                target.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                target.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
}
