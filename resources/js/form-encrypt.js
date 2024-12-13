/**
 * Form Encrypt
 *
 * @author Aleksandr Kireev <hello@bespredel.name>
 * @website https://bespredel.name
 * @license MIT
 */

(function () {
    class FormEncryptor {
        constructor(publicKey) {
            if (!publicKey) {
                throw new Error('Public key is required for encryption.');
            }
            this.publicKey = publicKey;

            if (typeof JSEncrypt !== 'function') {
                throw new Error('JSEncrypt library is not available.');
            }

            this.encryptor = new JSEncrypt();
            this.encryptor.setPublicKey(this.publicKey);
        }

        /**
         * Check if encryption is available
         *
         * @return {boolean}
         */
        isEncryptionAvailable() {
            const testString = 'test';
            const encrypted = this.encryptor.encrypt(testString);
            return encrypted !== false;
        }

        /**
         * Encrypt field value
         *
         * @param {string} value
         * @return {string}
         */
        encryptField(value) {
            const encrypted = this.encryptor.encrypt(value);
            if (!encrypted) {
                throw new Error('Failed to encrypt field value.');
            }
            return encrypted;
        }

        /**
         * Update status message
         *
         * @param {string} message
         * @param {boolean} isError
         */
        updateStatus(message, isError = false) {
            const statusElement = document.querySelector('.encrypt-form-status');
            if (statusElement) {
                statusElement.textContent = message;
                statusElement.style.color = isError ? 'red' : 'green';
            }
        }

        /**
         * Ask user for action
         *
         * @param {HTMLFormElement} form
         */
        askUserForAction(form) {
            const userDecision = confirm('Encryption is not available. Do you want to submit the form without encryption?');
            if (userDecision) {
                form.submit();
            } else {
                this.updateStatus('Form submission canceled by user.', true);
            }
        }

        /**
         * Encrypt form fields
         *
         * @param {HTMLFormElement} form
         */
        encryptForm(form) {
            const fields = form.querySelectorAll('[data-encrypt="true"]');
            try {
                fields.forEach(field => {
                    if (field.value) {
                        if (!field.value || field.type === 'file' || field.type === 'checkbox' || field.type === 'radio') {
                            console.warn(`Encryption skipped for unsupported input: ${field.name}`);
                            return;
                        }

                        const encryptedValue = this.encryptField(field.value);

                        if (field.type !== 'text' && field.type !== 'password' && field.type !== 'textarea' && field.type !== 'email') {
                            // Create a hidden input for the encrypted value
                            const hiddenField = document.createElement('input');
                            hiddenField.type = 'hidden';
                            hiddenField.name = field.name;
                            hiddenField.value = `ENCF:${encryptedValue}`;
                            form.appendChild(hiddenField);

                            // Clear the original number field value to prevent submission
                            field.name = ''; // Remove the name to avoid duplication
                            field.value = ''; // Clear the value
                        } else {
                            field.value = `ENCF:${encryptedValue}`;
                        }
                    }
                });
                this.updateStatus('Form encrypted successfully.');
            } catch (error) {
                this.updateStatus('Failed to encrypt form.', true);
                console.error('Error during form encryption:', error);
            }
        }

        /**
         * Attach encryption to forms
         */
        attachToForms() {
            const forms = document.querySelectorAll('[data-encrypt-form]');
            forms.forEach(form => {
                if (form.method.toLowerCase() !== 'post') {
                    console.warn(`Skipping form with method "${form.method}": ${form.name || 'unnamed form'}`);
                    return;
                }

                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    try {
                        if (this.isEncryptionAvailable()) {
                            this.encryptForm(form);
                            form.submit();
                        } else {
                            console.warn('Encryption is not available. Asking user for action.');
                            this.updateStatus('Encryption not available.', true);
                            this.askUserForAction(form);
                        }
                    } catch (error) {
                        console.error('Form encryption failed:', error);
                        this.askUserForAction(form); // Fallback: ask user what to do
                    }
                });
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        try {
            if (!window.ENCRYPTION_FORM_PUBLIC_KEY) {
                console.error('ENCRYPTION_FORM_PUBLIC_KEY is not set!');
                return;
            }

            const formEncryptor = new FormEncryptor(window.ENCRYPTION_FORM_PUBLIC_KEY);
            formEncryptor.attachToForms();
        } catch (error) {
            console.error('Failed to initialize FormEncryptor:', error);
        }
    });
})();