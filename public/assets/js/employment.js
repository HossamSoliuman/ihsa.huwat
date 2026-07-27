(function () {
    'use strict';

    document.documentElement.classList.add('employment-js');

    function toArray(collection) {
        return Array.prototype.slice.call(collection || []);
    }

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' بايت';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' كيلوبايت';
        return (bytes / (1024 * 1024)).toFixed(1) + ' ميجابايت';
    }

    function initializePublicShell() {
        var toggle = document.querySelector('[data-public-nav-toggle]');
        var navigation = document.querySelector('[data-public-nav]');

        function closeNavigation() {
            if (!toggle || !navigation) return;
            navigation.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        if (toggle && navigation) {
            toggle.addEventListener('click', function () {
                var willOpen = !navigation.classList.contains('is-open');
                navigation.classList.toggle('is-open', willOpen);
                toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
            navigation.addEventListener('click', function (event) {
                if (event.target.closest('a')) closeNavigation();
            });
            document.addEventListener('click', function (event) {
                if (!navigation.contains(event.target) && !toggle.contains(event.target)) closeNavigation();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeNavigation();
                    toggle.focus();
                }
            });
        }

        var themeToggle = document.querySelector('[data-employment-theme-toggle]');
        if (themeToggle) {
            themeToggle.addEventListener('click', function () {
                var current = document.documentElement.getAttribute('data-theme') || 'light';
                var next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                var themeMeta = document.querySelector('meta[name="theme-color"]');
                if (themeMeta) themeMeta.setAttribute('content', next === 'dark' ? '#081c2d' : '#f4f7f8');
            });
        }
    }

    function initializeApplicationWizard() {
        var form = document.querySelector('[data-employment-application]');
        if (!form) return;

        var panels = toArray(form.querySelectorAll('[data-step-panel]'));
        var stepItems = toArray(document.querySelectorAll('[data-step-item]'));
        var currentInput = form.querySelector('[data-current-step]');
        var initialStep = parseInt(form.getAttribute('data-initial-step') || '1', 10);
        var currentStep = Math.min(4, Math.max(1, initialStep || 1));
        var maximumVisitedStep = currentStep;
        var status = form.querySelector('[data-wizard-status]');
        var draftStatus = document.querySelector('[data-draft-status]');
        var submitButton = form.querySelector('[data-application-submit]');
        var maximumFileSize = 10 * 1024 * 1024;
        var draftKey = 'employment-application-draft:' + (form.getAttribute('data-job-id') || 'unknown');
        var presentationControls = toArray(form.querySelectorAll('input, select, textarea')).filter(function (control) {
            return control.type !== 'hidden';
        });

        function controlContainer(control) {
            return control.closest('.employment-field, .employment-upload-field, .employment-consent-box');
        }

        function controlHasValue(control) {
            if (control.type === 'radio' || control.type === 'checkbox') return control.checked;
            if (control.type === 'file') return Boolean(control.files && control.files.length);
            return String(control.value || '').trim() !== '';
        }

        function updateControlPresentation(control, interacted) {
            var container = controlContainer(control);
            if (!container) return;
            var isInvalid = interacted && !control.checkValidity();
            container.classList.toggle('is-invalid', isInvalid);
            container.classList.toggle('is-valid', interacted && !isInvalid && controlHasValue(control));
            if (isInvalid) control.setAttribute('aria-invalid', 'true');
            else if (interacted) control.removeAttribute('aria-invalid');
        }

        presentationControls.forEach(function (control) {
            if (control.getAttribute('aria-invalid') === 'true') updateControlPresentation(control, true);
            control.addEventListener('invalid', function () { updateControlPresentation(control, true); });
            control.addEventListener('blur', function () { updateControlPresentation(control, true); });
            control.addEventListener('change', function () { updateControlPresentation(control, true); });
            control.addEventListener('input', function () {
                var container = controlContainer(control);
                if (container && container.classList.contains('is-invalid')) updateControlPresentation(control, true);
            });
        });

        function announce(message) {
            if (!status) return;
            status.textContent = message || '';
        }

        function panelForStep(step) {
            return form.querySelector('[data-step-panel="' + step + '"]');
        }

        function stepForElement(element) {
            var panel = element && element.closest ? element.closest('[data-step-panel]') : null;
            return panel ? parseInt(panel.getAttribute('data-step-panel'), 10) : 1;
        }

        function updateStepper() {
            stepItems.forEach(function (item) {
                var itemStep = parseInt(item.getAttribute('data-step-item'), 10);
                var button = item.querySelector('[data-step-target]');
                item.classList.toggle('is-active', itemStep === currentStep);
                item.classList.toggle('is-complete', itemStep < maximumVisitedStep && itemStep !== currentStep);
                if (button) {
                    if (itemStep === currentStep) button.setAttribute('aria-current', 'step');
                    else button.removeAttribute('aria-current');
                    button.disabled = itemStep > maximumVisitedStep;
                }
            });
        }

        function updateReview() {
            toArray(form.querySelectorAll('[data-review-value]')).forEach(function (target) {
                var name = target.getAttribute('data-review-value');
                var controls = toArray(form.querySelectorAll('[name="' + name + '"]'));
                var value = '—';

                if (controls.length) {
                    var control = controls[0];
                    if (control.type === 'radio') {
                        var checked = controls.find(function (item) { return item.checked; });
                        if (checked) {
                            var radioLabel = checked.closest('label');
                            value = radioLabel ? radioLabel.textContent.trim() : checked.value;
                        }
                    } else if (control.tagName === 'SELECT') {
                        value = control.value && control.selectedOptions.length
                            ? control.selectedOptions[0].textContent.trim()
                            : '—';
                    } else {
                        value = control.value.trim() || '—';
                    }
                }
                target.textContent = value;
            });

            toArray(form.querySelectorAll('[data-review-files]')).forEach(function (target) {
                var input = form.querySelector('#' + target.getAttribute('data-review-files'));
                if (!input || !input.files || !input.files.length) {
                    target.textContent = target.getAttribute('data-review-files') === 'cv_file' ? 'لم يُحدد ملف' : 'لا يوجد';
                    return;
                }
                target.textContent = toArray(input.files).map(function (file) { return file.name; }).join('، ');
            });
        }

        function showStep(step, shouldFocus) {
            currentStep = Math.min(4, Math.max(1, step));
            maximumVisitedStep = Math.max(maximumVisitedStep, currentStep);
            if (currentInput) currentInput.value = String(currentStep);

            panels.forEach(function (panel) {
                var isCurrent = parseInt(panel.getAttribute('data-step-panel'), 10) === currentStep;
                panel.hidden = !isCurrent;
                panel.classList.toggle('is-active', isCurrent);
            });
            updateStepper();
            if (currentStep === 4) updateReview();
            announce('');

            if (shouldFocus) {
                var activePanel = panelForStep(currentStep);
                var heading = activePanel ? activePanel.querySelector('h2') : null;
                if (heading) {
                    heading.setAttribute('tabindex', '-1');
                    heading.focus({ preventScroll: true });
                    heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        }

        function validateFiles(input) {
            input.setCustomValidity('');
            if (!input.files) return true;

            if (input.id === 'certificate_files' && input.files.length > 5) {
                input.setCustomValidity('يمكن إرفاق خمسة ملفات كحد أقصى.');
                return false;
            }

            var validExtensions = /\.(pdf|jpe?g|png)$/i;
            var validMimeTypes = ['application/pdf', 'image/jpeg', 'image/png', ''];
            var invalidFile = toArray(input.files).find(function (file) {
                return file.size < 1 || file.size > maximumFileSize || !validExtensions.test(file.name) || validMimeTypes.indexOf(file.type) === -1;
            });
            if (invalidFile) {
                input.setCustomValidity('اختر ملف PDF أو JPEG أو PNG لا يتجاوز 10 ميجابايت.');
                return false;
            }
            return true;
        }

        function validatePanel(panel, report) {
            if (!panel) return true;
            var controls = toArray(panel.querySelectorAll('input, select, textarea')).filter(function (control) {
                return !control.disabled && control.type !== 'hidden';
            });
            var firstInvalid = null;

            controls.forEach(function (control) {
                if (control.type === 'file') validateFiles(control);
                updateControlPresentation(control, true);
                if (!control.checkValidity() && !firstInvalid) firstInvalid = control;
            });

            if (firstInvalid && report) {
                firstInvalid.reportValidity();
                firstInvalid.focus({ preventScroll: true });
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                announce('يرجى إكمال الحقول المطلوبة في هذه الخطوة.');
            }
            return !firstInvalid;
        }

        toArray(form.querySelectorAll('[data-wizard-next]')).forEach(function (button) {
            button.addEventListener('click', function () {
                var panel = panelForStep(currentStep);
                if (!validatePanel(panel, true)) return;
                showStep(currentStep + 1, true);
            });
        });

        toArray(form.querySelectorAll('[data-wizard-previous]')).forEach(function (button) {
            button.addEventListener('click', function () { showStep(currentStep - 1, true); });
        });

        stepItems.forEach(function (item) {
            var button = item.querySelector('[data-step-target]');
            if (!button) return;
            button.addEventListener('click', function () {
                var target = parseInt(button.getAttribute('data-step-target'), 10);
                if (target <= maximumVisitedStep) showStep(target, true);
            });
        });

        toArray(form.querySelectorAll('[data-edit-step]')).forEach(function (button) {
            button.addEventListener('click', function () {
                showStep(parseInt(button.getAttribute('data-edit-step'), 10), true);
            });
        });

        toArray(form.querySelectorAll('input[type="file"]')).forEach(function (input) {
            var list = form.querySelector('[data-file-list="' + input.id + '"]');

            function renderFileList() {
                validateFiles(input);
                if (!list) return;
                list.innerHTML = '';
                toArray(input.files).forEach(function (file) {
                    var item = document.createElement('li');
                    var name = document.createElement('span');
                    var size = document.createElement('small');
                    name.textContent = file.name;
                    size.textContent = formatBytes(file.size);
                    item.appendChild(name);
                    item.appendChild(size);
                    list.appendChild(item);
                });
                updateReview();
            }

            input.addEventListener('change', renderFileList);
            var zone = input.closest('[data-upload-zone]');
            if (zone) {
                ['dragenter', 'dragover'].forEach(function (eventName) {
                    zone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        zone.classList.add('is-dragging');
                    });
                });
                ['dragleave', 'drop'].forEach(function (eventName) {
                    zone.addEventListener(eventName, function (event) {
                        event.preventDefault();
                        zone.classList.remove('is-dragging');
                    });
                });
                zone.addEventListener('drop', function (event) {
                    if (!event.dataTransfer || !event.dataTransfer.files.length) return;
                    try {
                        var transfer = new DataTransfer();
                        var droppedFiles = toArray(event.dataTransfer.files);
                        if (!input.multiple) droppedFiles = droppedFiles.slice(0, 1);
                        droppedFiles.forEach(function (file) { transfer.items.add(file); });
                        input.files = transfer.files;
                        renderFileList();
                    } catch (error) {
                        input.focus();
                    }
                });
            }
        });

        toArray(form.querySelectorAll('textarea[maxlength]')).forEach(function (textarea) {
            var counter = form.querySelector('[data-character-count="' + textarea.id + '"]');
            if (!counter) return;
            var hint = counter.textContent;
            function updateCounter() {
                counter.textContent = textarea.value.length + ' / ' + textarea.maxLength + ' — ' + hint.replace(/^.*?—\s*/, '');
            }
            textarea.addEventListener('input', updateCounter);
            updateCounter();
        });

        function saveDraft() {
            var values = {};
            toArray(form.elements).forEach(function (control) {
                if (!control.name || control.disabled || control.type === 'file' || control.type === 'submit' || control.type === 'button') return;
                if (['csrf_token', 'MAX_FILE_SIZE', 'job_id', 'current_step', 'website', 'consent'].indexOf(control.name) !== -1) return;
                if ((control.type === 'radio' || control.type === 'checkbox') && !control.checked) return;
                values[control.name] = control.value;
            });
            try {
                sessionStorage.setItem(draftKey, JSON.stringify({ savedAt: Date.now(), values: values, step: currentStep }));
                if (draftStatus) draftStatus.textContent = 'تم حفظ البيانات مؤقتاً في هذه الجلسة. المرفقات لا تُحفظ.';
            } catch (error) {
                if (draftStatus) draftStatus.textContent = 'تعذر حفظ المسودة في هذا المتصفح.';
            }
        }

        function restoreDraft() {
            if (form.getAttribute('data-has-server-values') === '1') return;
            var rawDraft;
            try { rawDraft = sessionStorage.getItem(draftKey); } catch (error) { return; }
            if (!rawDraft) return;

            try {
                var draft = JSON.parse(rawDraft);
                if (!draft || !draft.values || Date.now() - draft.savedAt > 12 * 60 * 60 * 1000) {
                    sessionStorage.removeItem(draftKey);
                    return;
                }
                Object.keys(draft.values).forEach(function (name) {
                    var controls = toArray(form.querySelectorAll('[name="' + name + '"]'));
                    controls.forEach(function (control) {
                        if (control.type === 'radio' || control.type === 'checkbox') control.checked = control.value === draft.values[name];
                        else control.value = draft.values[name];
                    });
                });
                maximumVisitedStep = Math.min(3, Math.max(1, parseInt(draft.step || '1', 10)));
                showStep(maximumVisitedStep, false);
                if (draftStatus) draftStatus.textContent = 'تمت استعادة المسودة المحفوظة في هذه الجلسة.';
            } catch (error) {
                try { sessionStorage.removeItem(draftKey); } catch (storageError) { /* no-op */ }
            }
        }

        toArray(form.querySelectorAll('[data-save-draft]')).forEach(function (button) {
            button.addEventListener('click', saveDraft);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            if (form.getAttribute('data-submitting') === '1') return;

            for (var step = 1; step <= 4; step += 1) {
                var panel = panelForStep(step);
                if (!validatePanel(panel, false)) {
                    maximumVisitedStep = Math.max(maximumVisitedStep, step);
                    showStep(step, false);
                    validatePanel(panel, true);
                    return;
                }
            }

            form.setAttribute('data-submitting', '1');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.classList.add('is-loading');
                var label = submitButton.querySelector('span');
                if (label) label.textContent = 'جارٍ إرسال الطلب…';
            }
            announce('جارٍ رفع المرفقات وإرسال الطلب. يرجى عدم إغلاق الصفحة.');
            try { sessionStorage.removeItem(draftKey); } catch (error) { /* no-op */ }
            HTMLFormElement.prototype.submit.call(form);
        });

        var errorSummary = document.querySelector('[data-error-summary]');
        if (errorSummary) {
            errorSummary.addEventListener('click', function (event) {
                var link = event.target.closest('a[href^="#"]');
                if (!link) return;
                var field = document.getElementById(link.getAttribute('href').slice(1));
                if (!field) return;
                event.preventDefault();
                maximumVisitedStep = Math.max(maximumVisitedStep, stepForElement(field));
                showStep(stepForElement(field), false);
                field.focus();
                field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
            window.setTimeout(function () { errorSummary.focus(); }, 50);
        }

        restoreDraft();
        showStep(currentStep, false);
    }

    function initializeReceipt() {
        var copyButton = document.querySelector('[data-copy-reference]');
        var reference = document.querySelector('[data-application-reference]');
        var copyStatus = document.querySelector('[data-copy-status]');
        if (copyButton && reference) {
            copyButton.addEventListener('click', function () {
                var value = reference.textContent.trim();
                var promise;
                if (navigator.clipboard && window.isSecureContext) {
                    promise = navigator.clipboard.writeText(value);
                } else {
                    promise = new Promise(function (resolve, reject) {
                        var input = document.createElement('textarea');
                        input.value = value;
                        input.setAttribute('readonly', '');
                        input.style.position = 'fixed';
                        input.style.opacity = '0';
                        document.body.appendChild(input);
                        input.select();
                        try { document.execCommand('copy') ? resolve() : reject(new Error('copy')); }
                        catch (error) { reject(error); }
                        document.body.removeChild(input);
                    });
                }
                promise.then(function () {
                    if (copyStatus) copyStatus.textContent = 'تم نسخ الرقم المرجعي.';
                }).catch(function () {
                    if (copyStatus) copyStatus.textContent = 'تعذر النسخ تلقائياً؛ حدّد الرقم وانسخه يدوياً.';
                });
            });
        }

    }

    document.addEventListener('DOMContentLoaded', function () {
        initializePublicShell();
        initializeApplicationWizard();
        initializeReceipt();
    });
}());
