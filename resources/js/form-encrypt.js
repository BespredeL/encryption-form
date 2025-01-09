/**
 * Form Encrypt
 *
 * @author Aleksandr Kireev <hello@bespredel.name>
 * @website https://bespredel.name
 * @license MIT
 */

(function (global, factory) {
    if (typeof module === 'object' && typeof module.exports === 'object') {
        module.exports = factory();
    } else if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        global.FormEncryptor = factory();
    }
})(typeof window !== 'undefined' ? window : this, function () {
    class FormEncryptor {
        constructor(publicKey) {
            if (!publicKey) {
                throw new Error('Public key is required for encryption.');
            }
            this.publicKey = publicKey;

            if (typeof JSEncrypt !== 'function') {
                console.error('JSEncrypt library is not available. Please ensure the library is loaded and try again.');
                this.encryptor = null; // Disable encryption functionality
                return;
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
        encryptValue(value) {
            if (!this.encryptor) {
                throw new Error('Encryption functionality is disabled.');
            }

            if (typeof value !== 'string' || value.trim() === '') {
                throw new Error('Invalid input value. Value must be a non-empty string.');
            }

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
                statusElement.textContent = window.ENCRYPTION_FORM.trans(message);
                if (!statusElement.style.color) {
                    statusElement.style.color = isError ? 'red' : 'green';
                }
            }
        }

        /**
         * Ask user for action
         *
         * @param {HTMLFormElement} form
         */
        askUserForAction(form) {
            // Create modal elements
            const modal = document.createElement('div');
            modal.className = 'form-encrypt-modal';

            const modalContent = document.createElement('div');
            modalContent.className = 'form-encrypt-modal-content';

            const message = document.createElement('p');
            message.textContent = window.ENCRYPTION_FORM.trans('Encryption is not available. Do you want to submit the form without data encryption?');
            modalContent.appendChild(message);

            const buttonContainer = document.createElement('div');
            buttonContainer.className = 'form-encrypt-modal-buttons';

            const confirmButton = document.createElement('button');
            confirmButton.textContent = window.ENCRYPTION_FORM.trans('Submit');
            confirmButton.className = 'form-encrypt-modal-confirm';
            confirmButton.onclick = () => {
                document.body.removeChild(modal);
                form.submit();
            };

            const cancelButton = document.createElement('button');
            cancelButton.textContent = window.ENCRYPTION_FORM.trans('Cancel');
            cancelButton.className = 'form-encrypt-modal-cancel';
            cancelButton.onclick = () => {
                document.body.removeChild(modal);
                this.updateStatus('Form submission canceled by user.', true);
            };

            buttonContainer.appendChild(confirmButton);
            buttonContainer.appendChild(cancelButton);

            modalContent.appendChild(buttonContainer);
            modal.appendChild(modalContent);

            document.body.appendChild(modal);
        }

        /**
         * Encrypt form fields
         *
         * @param {HTMLFormElement} form
         */
        encryptForm(form) {
            const fields = form.querySelectorAll('[data-encrypt="true"]');
            const targetFields = fields.length > 0 ? fields : form.querySelectorAll('input, textarea');

            try {
                targetFields.forEach(field => {
                    if (field.getAttribute('data-encrypt') === 'false') {
                        console.warn(`Skipping field: ${field.name}, encryption is disabled.`);
                        return;
                    }

                    const {value, type, name} = field;

                    if (!value || name === "_token" || type === 'file' || type === 'checkbox' || type === 'radio') {
                        console.warn(`Encryption skipped for unsupported input: ${name}`);
                        return;
                    }

                    const encryptedValue = this.encryptValue(value);

                    if (type !== 'text' && type !== 'password' && type !== 'textarea' && type !== 'email') {
                        // Create a hidden input for the encrypted value
                        const hiddenField = document.createElement('input');
                        hiddenField.type = 'hidden';
                        hiddenField.name = name;
                        hiddenField.value = `${window.ENCRYPTION_FORM.prefix}${encryptedValue}`;
                        form.appendChild(hiddenField);

                        // Clear the original number field value to prevent submission
                        field.name = ''; // Remove the name to avoid duplication
                        field.value = ''; // Clear the value
                    } else {
                        field.value = `${window.ENCRYPTION_FORM.prefix}${encryptedValue}`;
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
                if (form.method.toLowerCase() === 'get') {
                    console.warn(`Skipping form with method "${form.method}": ${form.name || 'unnamed form'}`);
                    return;
                }

                // Check if encryption is available
                if (this.isEncryptionAvailable()) {
                    this.updateStatus('Encryption available.', false);
                } else {
                    this.updateStatus('Encryption not available.', true);
                }

                let submitTimeout = null; // For debouncing

                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    if (submitTimeout) clearTimeout(submitTimeout);

                    submitTimeout = setTimeout(() => {
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
                    }, 300);
                });
            });
        }
    }

    return FormEncryptor;
});

document.addEventListener('DOMContentLoaded', () => {
    try {
        if (!window.ENCRYPTION_FORM.public_key) {
            console.error('ENCRYPTION_FORM_PUBLIC_KEY is not set!');
            return;
        }

        const formEncryptor = new FormEncryptor(window.ENCRYPTION_FORM.public_key);
        formEncryptor.attachToForms();
    } catch (error) {
        console.error('Failed to initialize FormEncryptor:', error);
    }
});