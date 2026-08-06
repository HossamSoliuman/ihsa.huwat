function initializeInformationPortal() {
    const form = document.querySelector('[data-info-form]');

    if (!form) {
        return;
    }

    const panels = Array.from(form.querySelectorAll('[data-info-panel]'));
    const steps = Array.from(form.querySelectorAll('[data-info-step]'));
    const currentStepInput = form.querySelector('[data-info-current-step]');
    const announcer = form.querySelector('[data-info-announcer]');
    const progressStep = form.querySelector('[data-info-progress-step]');
    const progressTitle = form.querySelector('[data-info-progress-title]');
    const progressBar = form.querySelector('[data-info-progress-bar]');
    const submitButton = form.querySelector('[data-info-submit]');
    const totalSteps = panels.length;
    const scrollBehavior = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
    let currentStep = Math.min(totalSteps, Math.max(1, Number.parseInt(form.dataset.startStep || '1', 10)));
    let maximumVisitedStep = currentStep;
    let hasUnsavedChanges = form.dataset.hasUnsavedInput === '1';

    form.classList.add('is-enhanced');

    function panelForStep(step) {
        return panels.find((panel) => Number.parseInt(panel.dataset.infoPanel, 10) === step);
    }

    function announce(message) {
        if (!announcer) {
            return;
        }

        announcer.textContent = '';
        window.requestAnimationFrame(() => {
            announcer.textContent = message;
        });
    }

    function reviewValue(control) {
        if (control instanceof HTMLSelectElement) {
            return control.value ? control.selectedOptions[0].textContent.trim() : '—';
        }

        if (!(control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement)) {
            return '—';
        }

        if (control.type === 'file') {
            return control.files?.[0]?.name || 'غير مرفقة';
        }

        if (control.type === 'date' && control.value) {
            const [year, month, day] = control.value.split('-').map(Number);
            const date = new Date(year, month - 1, day);

            return new Intl.DateTimeFormat('ar-SA', {
                calendar: 'gregory',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }).format(date);
        }

        return control.value.trim() || '—';
    }

    function maskIdentity(value) {
        if (value === '—' || value.length < 5) {
            return value;
        }

        return `${value.slice(0, 2)}••••••${value.slice(-2)}`;
    }

    function updateReview() {
        form.querySelectorAll('[data-info-review]').forEach((target) => {
            const control = form.elements.namedItem(target.dataset.infoReview);
            let value = reviewValue(control);

            if (target.dataset.infoMask === 'identity') {
                value = maskIdentity(value);
            }

            target.textContent = value;
        });
    }

    function updateLivePreviews() {
        form.querySelectorAll('[data-info-live-source]').forEach((control) => {
            const targets = form.querySelectorAll(`[data-info-live-target="${control.dataset.infoLiveSource}"]`);
            const emptyValue = control.dataset.infoLiveSource === 'boat_name' ? 'قارب بدون اسم' : '—';
            const value = control.value.trim() || emptyValue;

            targets.forEach((target) => {
                target.textContent = value;
            });
        });
    }

    function updateProgress() {
        const activeStep = steps.find((item) => Number.parseInt(item.dataset.infoStep, 10) === currentStep);
        const label = activeStep?.querySelector('.info-step-label strong')?.textContent.trim() || '';

        if (progressStep) {
            progressStep.textContent = `الخطوة ${currentStep} من ${totalSteps}`;
        }

        if (progressTitle) {
            progressTitle.textContent = label;
        }

        if (progressBar) {
            progressBar.style.width = `${(currentStep / totalSteps) * 100}%`;
        }
    }

    function updateSteps() {
        steps.forEach((item) => {
            const step = Number.parseInt(item.dataset.infoStep, 10);
            const button = item.querySelector('[data-info-step-target]');
            const panel = panelForStep(step);
            const hasError = controlsForPanel(panel).some((control) => !control.checkValidity())
                || Boolean(panel?.querySelector('[aria-invalid="true"]'));
            const hasBeenVisited = step <= maximumVisitedStep;

            item.classList.toggle('is-active', step === currentStep);
            item.classList.toggle('is-complete', step < maximumVisitedStep && step !== currentStep && !hasError);
            item.classList.toggle('is-error', hasBeenVisited && hasError && step !== currentStep);

            if (!button) {
                return;
            }

            button.disabled = step > maximumVisitedStep;
            button.setAttribute('aria-disabled', step > maximumVisitedStep ? 'true' : 'false');

            if (step === currentStep) {
                button.setAttribute('aria-current', 'step');
            } else {
                button.removeAttribute('aria-current');
            }
        });
    }

    function showStep(step, moveFocus = true) {
        currentStep = Math.min(totalSteps, Math.max(1, step));
        maximumVisitedStep = Math.max(maximumVisitedStep, currentStep);

        if (currentStepInput) {
            currentStepInput.value = String(currentStep);
        }

        panels.forEach((panel) => {
            const isActive = Number.parseInt(panel.dataset.infoPanel, 10) === currentStep;
            panel.hidden = !isActive;
            panel.classList.toggle('is-active', isActive);
        });

        if (currentStep === totalSteps) {
            updateReview();
            updateCollectionSummaries();
        }

        updateSteps();
        updateProgress();
        announce(`الخطوة ${currentStep} من ${totalSteps}`);

        if (moveFocus) {
            const heading = panelForStep(currentStep)?.querySelector('h2');

            if (heading) {
                heading.focus({ preventScroll: true });
                heading.scrollIntoView({ behavior: scrollBehavior, block: 'center' });
            }
        }
    }

    function controlsForPanel(panel) {
        if (!panel) {
            return [];
        }

        return Array.from(panel.querySelectorAll('input, select, textarea'))
            .filter((control) => !control.disabled && control.type !== 'hidden');
    }

    function validatePanel(panel, report = true) {
        const firstInvalid = controlsForPanel(panel).find((control) => !control.checkValidity());

        if (firstInvalid && report) {
            firstInvalid.setAttribute('aria-invalid', 'true');
            firstInvalid.reportValidity();
            firstInvalid.focus({ preventScroll: true });
            firstInvalid.scrollIntoView({ behavior: scrollBehavior, block: 'center' });
            announce('أكمل الحقول المطلوبة قبل الانتقال.');
            updateSteps();
        }

        return !firstInvalid;
    }

    function focusTarget(target) {
        const focusable = target.matches('input, select, textarea, button')
            ? target
            : target.querySelector('input, select, textarea, button');

        if (!(focusable instanceof HTMLElement)) {
            return;
        }

        focusable.focus({ preventScroll: true });
        focusable.scrollIntoView({ behavior: scrollBehavior, block: 'center' });
    }

    form.querySelectorAll('[data-info-next]').forEach((button) => {
        button.addEventListener('click', () => {
            if (validatePanel(panelForStep(currentStep))) {
                showStep(currentStep + 1);
            }
        });
    });

    form.querySelectorAll('[data-info-previous]').forEach((button) => {
        button.addEventListener('click', () => showStep(currentStep - 1));
    });

    form.querySelectorAll('[data-info-step-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const target = Number.parseInt(button.dataset.infoStepTarget, 10);

            if (target <= maximumVisitedStep) {
                showStep(target);
            }
        });
    });

    form.querySelectorAll('[data-info-edit]').forEach((button) => {
        button.addEventListener('click', () => showStep(Number.parseInt(button.dataset.infoEdit, 10)));
    });

    form.querySelectorAll('[data-info-error-target]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const target = document.getElementById(link.dataset.infoErrorTarget);

            if (!(target instanceof HTMLElement)) {
                return;
            }

            const panel = target.closest('[data-info-panel]');

            if (!panel) {
                return;
            }

            event.preventDefault();
            showStep(Number.parseInt(panel.dataset.infoPanel, 10), false);
            window.setTimeout(() => focusTarget(target), 50);
        });
    });

    function reconcileControlValidity(control) {
        if (!(control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement)) {
            return;
        }

        if (control.checkValidity()) {
            control.removeAttribute('aria-invalid');

            const describedBy = (control.getAttribute('aria-describedby') || '').split(/\s+/);
            describedBy.forEach((id) => {
                const message = document.getElementById(id);

                if (message?.classList.contains('info-field-error')) {
                    message.hidden = true;
                }
            });
        }

        updateSteps();
    }

    function reindexRows(list, rowSelector, collectionNames, idPrefix) {
        // A row can carry more than one collection: crew fields and the crew photo travel together.
        const collections = [].concat(collectionNames).map((name) => ({
            name,
            pattern: new RegExp(`${name}\\[(?:\\d+|__INDEX__)\\]`, 'g'),
        }));
        const idPattern = new RegExp(`${idPrefix}_(?:\\d+|__INDEX__)_`, 'g');

        Array.from(list.querySelectorAll(rowSelector)).forEach((row, index) => {
            row.querySelectorAll('[name]').forEach((control) => {
                control.name = collections.reduce(
                    (name, collection) => name.replace(collection.pattern, `${collection.name}[${index}]`),
                    control.name,
                );
            });

            row.querySelectorAll('[id]').forEach((element) => {
                element.id = element.id.replace(idPattern, `${idPrefix}_${index}_`);
            });

            row.querySelectorAll('[for]').forEach((label) => {
                label.htmlFor = label.htmlFor.replace(idPattern, `${idPrefix}_${index}_`);
            });

            row.querySelectorAll('[aria-describedby]').forEach((element) => {
                element.setAttribute('aria-describedby', element.getAttribute('aria-describedby').replace(idPattern, `${idPrefix}_${index}_`));
            });

            row.querySelectorAll('[data-info-row-number], [data-info-row-title]').forEach((target) => {
                target.textContent = String(index + 1);
            });
        });
    }

    const crewList = form.querySelector('[data-info-crew-list]');
    const crewTemplate = form.querySelector('[data-info-crew-template]');
    const crewCount = form.querySelector('[data-info-crew-count]');

    function updateCrewCollection() {
        if (!crewList) {
            return;
        }

        reindexRows(crewList, '[data-info-crew-row]', ['crew_members', 'crew_photos'], 'crew');
        const rows = Array.from(crewList.querySelectorAll('[data-info-crew-row]'));

        rows.forEach((row) => {
            const removeButton = row.querySelector('[data-info-remove-crew]');

            if (removeButton) {
                removeButton.disabled = rows.length === 1;
                removeButton.setAttribute('aria-disabled', rows.length === 1 ? 'true' : 'false');
            }
        });

        if (crewCount) {
            crewCount.value = String(rows.length);
        }

        form.querySelectorAll('[data-info-crew-total], [data-info-review-crew-count]').forEach((target) => {
            target.textContent = String(rows.length);
        });
    }

    form.querySelector('[data-info-add-crew]')?.addEventListener('click', () => {
        if (!crewList || !crewTemplate || crewList.querySelectorAll('[data-info-crew-row]').length >= 50) {
            return;
        }

        const index = crewList.querySelectorAll('[data-info-crew-row]').length;
        crewList.insertAdjacentHTML('beforeend', crewTemplate.innerHTML.replaceAll('__INDEX__', String(index)));
        updateCrewCollection();
        hasUnsavedChanges = true;
        const newRow = crewList.lastElementChild;
        newRow?.querySelectorAll('[data-info-upload]').forEach(initializeUploadCard);
        focusTarget(newRow);
        announce(`تمت إضافة البحار رقم ${index + 1}.`);
    });

    crewList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-info-remove-crew]');

        if (!button || button.disabled) {
            return;
        }

        button.closest('[data-info-crew-row]')?.remove();
        updateCrewCollection();
        hasUnsavedChanges = true;
        announce('تم حذف سجل البحار.');
    });

    const toolList = form.querySelector('[data-info-tool-list]');
    const toolTemplate = form.querySelector('[data-info-tool-template]');

    function updateToolCollection() {
        if (!toolList) {
            return;
        }

        reindexRows(toolList, '[data-info-tool-row]', 'fishing_tools', 'tool');
        const rows = Array.from(toolList.querySelectorAll('[data-info-tool-row]'));

        rows.forEach((row) => {
            const removeButton = row.querySelector('[data-info-remove-tool]');

            if (removeButton) {
                removeButton.disabled = rows.length === 1;
                removeButton.setAttribute('aria-disabled', rows.length === 1 ? 'true' : 'false');
            }
        });

        updateToolMetrics();
    }

    function toolLabel(row) {
        const select = row.querySelector('select[name$="[type]"]');

        return select?.value ? select.selectedOptions[0].textContent.trim() : '—';
    }

    function updateToolMetrics() {
        if (!toolList) {
            return;
        }

        const rows = Array.from(toolList.querySelectorAll('[data-info-tool-row]'));
        const types = new Set();
        let total = 0;
        let primaryCount = 0;
        let primaryLabel = '—';

        rows.forEach((row) => {
            const type = row.querySelector('select[name$="[type]"]')?.value;
            const quantity = Number.parseInt(row.querySelector('input[name$="[quantity]"]')?.value || '0', 10);
            const primary = row.querySelector('[data-info-primary-tool]');

            if (type) {
                types.add(type);
            }

            total += Number.isNaN(quantity) ? 0 : quantity;

            if (primary?.checked) {
                primaryCount += 1;
                primaryLabel = toolLabel(row);
            }
        });

        const firstPrimaryControl = toolList.querySelector('[data-info-primary-tool]');
        firstPrimaryControl?.setCustomValidity(primaryCount === 1 ? '' : 'حدد أداة صيد أساسية واحدة.');

        form.querySelectorAll('[data-info-tools-total], [data-info-review-tool-total]').forEach((target) => {
            target.textContent = String(total);
        });
        form.querySelectorAll('[data-info-tools-types], [data-info-review-tool-types]').forEach((target) => {
            target.textContent = String(types.size);
        });
        form.querySelectorAll('[data-info-tools-primary]').forEach((target) => {
            target.textContent = String(primaryCount);
        });
        form.querySelectorAll('[data-info-review-primary-tool]').forEach((target) => {
            target.textContent = primaryLabel;
        });
    }

    form.querySelector('[data-info-add-tool]')?.addEventListener('click', () => {
        if (!toolList || !toolTemplate || toolList.querySelectorAll('[data-info-tool-row]').length >= 50) {
            return;
        }

        const index = toolList.querySelectorAll('[data-info-tool-row]').length;
        toolList.insertAdjacentHTML('beforeend', toolTemplate.innerHTML.replaceAll('__INDEX__', String(index)));
        updateToolCollection();
        hasUnsavedChanges = true;
        const newRow = toolList.lastElementChild;
        focusTarget(newRow);
        announce(`تمت إضافة أداة الصيد رقم ${index + 1}.`);
    });

    toolList?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-info-remove-tool]');

        if (!button || button.disabled) {
            return;
        }

        button.closest('[data-info-tool-row]')?.remove();
        updateToolCollection();
        hasUnsavedChanges = true;
        announce('تم حذف أداة الصيد.');
    });

    toolList?.addEventListener('change', (event) => {
        if (event.target.matches('[data-info-primary-tool]') && event.target.checked) {
            toolList.querySelectorAll('[data-info-primary-tool]').forEach((control) => {
                if (control !== event.target) {
                    control.checked = false;
                }
            });
        }

        if (event.target.matches('[data-info-primary-tool]') && !toolList.querySelector('[data-info-primary-tool]:checked')) {
            event.target.checked = true;
        }

        updateToolMetrics();
    });

    toolList?.addEventListener('input', updateToolMetrics);

    const region = form.querySelector('[data-info-region]');
    const governorate = form.querySelector('[data-info-governorate]');
    const cityField = form.querySelector('[data-info-city-field]');
    const citySelect = form.querySelector('[data-info-city]');
    const cityFallback = form.querySelector('[data-info-city-fallback]');

    function filterGovernorates() {
        if (!region || !governorate) {
            return;
        }

        const selectedRegion = region.value;
        const selectedOption = governorate.selectedOptions[0];

        Array.from(governorate.options).forEach((option, index) => {
            if (index === 0) {
                return;
            }

            const isVisible = !selectedRegion || option.dataset.region === selectedRegion;
            option.hidden = !isVisible;
            option.disabled = !isVisible;
        });

        if (selectedOption?.disabled) {
            governorate.value = '';
        }

        filterCities();
    }

    /**
     * The city dropdown only takes over for governorates that have a maintained list;
     * anywhere else the original free-text input stays in charge. Whichever control is
     * idle gets disabled so the two never submit `owner_city` twice.
     */
    function filterCities() {
        if (!citySelect || !cityFallback) {
            return;
        }

        const selectedGovernorate = governorate?.value ?? '';
        let matches = 0;

        Array.from(citySelect.options).forEach((option, index) => {
            if (index === 0) {
                return;
            }

            const isVisible = Boolean(selectedGovernorate) && option.dataset.governorate === selectedGovernorate;
            option.hidden = !isVisible;
            option.disabled = !isVisible;

            if (isVisible) {
                matches += 1;
            }
        });

        const usesList = matches > 0;

        if (usesList && citySelect.selectedOptions[0]?.disabled) {
            citySelect.value = '';
        }

        [[citySelect, usesList], [cityFallback, !usesList]].forEach(([control, isActive]) => {
            control.hidden = !isActive;
            control.disabled = !isActive;
            control.required = isActive;
        });

        const label = cityField?.querySelector('label');

        if (label) {
            label.htmlFor = usesList ? citySelect.id : cityFallback.id;
        }
    }

    region?.addEventListener('change', filterGovernorates);
    governorate?.addEventListener('change', filterCities);
    filterCities();

    const uploadCards = Array.from(form.querySelectorAll('[data-info-upload]'));

    function selectedDocumentFiles() {
        return Array.from(form.querySelectorAll('[data-info-document-grid] [data-info-file]'))
            .filter((input) => Boolean(input.files?.length));
    }

    function updateDocumentSummary() {
        const documentInputs = Array.from(form.querySelectorAll('[data-info-document-grid] [data-info-file]'));
        const files = selectedDocumentFiles();
        const requiredInputs = documentInputs.filter((input) => input.required);
        const requiredFiles = requiredInputs.filter((input) => Boolean(input.files?.length));
        const totalDocuments = documentInputs.length;
        const percentage = requiredInputs.length ? (requiredFiles.length / requiredInputs.length) * 100 : 100;

        form.querySelectorAll('[data-info-document-count]').forEach((target) => {
            target.textContent = String(files.length);
        });
        form.querySelectorAll('[data-info-required-document-count]').forEach((target) => {
            target.textContent = String(requiredFiles.length);
        });
        form.querySelectorAll('[data-info-review-document-count]').forEach((target) => {
            target.textContent = `${files.length} / ${totalDocuments}`;
        });
        form.querySelectorAll('[data-info-document-progress]').forEach((target) => {
            target.style.width = `${percentage}%`;
        });

        const list = form.querySelector('[data-info-review-document-list]');

        if (list) {
            list.replaceChildren();

            if (files.length === 0) {
                const item = document.createElement('li');
                item.textContent = 'لم تُحدد ملفات بعد';
                list.append(item);
            } else {
                files.forEach((input) => {
                    const item = document.createElement('li');
                    const label = input.closest('[data-info-upload]')?.querySelector('.info-upload-copy strong')?.textContent.trim() || 'مستند';
                    item.textContent = `${label.replace('*', '').trim()}: ${input.files[0].name}`;
                    list.append(item);
                });
            }
        }
    }

    function initializeUploadCard(upload) {
        const fileInput = upload.querySelector('[data-info-file]');
        const fileName = upload.querySelector('[data-info-file-name]');
        const fileRemove = upload.querySelector('[data-info-file-remove]');

        if (!(fileInput instanceof HTMLInputElement) || upload.dataset.infoUploadReady === '1') {
            return;
        }

        upload.dataset.infoUploadReady = '1';

        const imageOnly = !fileInput.accept.includes('application/pdf');
        const allowedExtensions = imageOnly ? ['jpg', 'jpeg', 'png'] : ['pdf', 'jpg', 'jpeg', 'png'];
        const allowedTypes = imageOnly
            ? ['image/jpeg', 'image/png']
            : ['application/pdf', 'image/jpeg', 'image/png'];

        const renderFile = () => {
            const file = fileInput.files?.[0];
            fileInput.setCustomValidity('');
            upload.classList.toggle('is-file-selected', Boolean(file));

            if (file) {
                const extension = file.name.split('.').pop()?.toLowerCase() || '';

                if (file.size > 10 * 1024 * 1024) {
                    fileInput.setCustomValidity('يجب ألا يتجاوز حجم الملف 10 ميجابايت.');
                } else if (!allowedTypes.includes(file.type) || !allowedExtensions.includes(extension)) {
                    fileInput.setCustomValidity(imageOnly ? 'ارفع صورة JPG أو PNG.' : 'ارفع ملف PDF أو صورة JPG أو PNG.');
                }
            }

            if (fileName) {
                fileName.textContent = file ? file.name : 'لم يتم اختيار ملف';
            }

            if (fileRemove) {
                fileRemove.hidden = !file;
            }

            reconcileControlValidity(fileInput);
            updateDocumentSummary();
        };

        fileInput.addEventListener('change', renderFile);

        ['dragenter', 'dragover'].forEach((eventName) => {
            upload.addEventListener(eventName, (event) => {
                event.preventDefault();
                upload.classList.add('is-dragging');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            upload.addEventListener(eventName, (event) => {
                event.preventDefault();
                upload.classList.remove('is-dragging');
            });
        });

        upload.addEventListener('drop', (event) => {
            if (!event.dataTransfer?.files.length) {
                return;
            }

            try {
                const transfer = new DataTransfer();
                transfer.items.add(event.dataTransfer.files[0]);
                fileInput.files = transfer.files;
                hasUnsavedChanges = true;
                renderFile();
            } catch {
                fileInput.focus();
            }
        });

        fileRemove?.addEventListener('click', () => {
            fileInput.value = '';
            hasUnsavedChanges = true;
            renderFile();
            fileInput.focus();
            announce('تمت إزالة الملف.');
        });
    }

    uploadCards.forEach(initializeUploadCard);

    function updateCollectionSummaries() {
        updateCrewCollection();
        updateToolCollection();
        updateDocumentSummary();
        updateReview();
    }

    ['input', 'change'].forEach((eventName) => {
        form.addEventListener(eventName, (event) => {
            hasUnsavedChanges = true;
            reconcileControlValidity(event.target);
            updateReview();
        });
    });

    form.querySelectorAll('[data-info-live-source]').forEach((control) => {
        control.addEventListener('input', updateLivePreviews);
    });

    form.querySelectorAll('.info-guidance').forEach((guidance) => {
        const toggle = document.createElement('button');
        toggle.className = 'info-guidance-toggle';
        toggle.type = 'button';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = '<span>نصائح هذه الخطوة</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"></path></svg>';
        guidance.prepend(toggle);

        toggle.addEventListener('click', () => {
            const isExpanded = guidance.classList.toggle('is-expanded');
            toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        });
    });

    form.addEventListener('submit', (event) => {
        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }

        updateCollectionSummaries();

        for (let step = 1; step <= totalSteps; step += 1) {
            if (!validatePanel(panelForStep(step), false)) {
                event.preventDefault();
                showStep(step, false);
                validatePanel(panelForStep(step));
                return;
            }
        }

        form.dataset.submitting = '1';
        hasUnsavedChanges = false;

        if (submitButton) {
            submitButton.disabled = true;
            const label = submitButton.querySelector('span');

            if (label) {
                label.textContent = 'جارٍ حفظ السجل…';
            }
        }

        announce('جارٍ حفظ جميع البيانات والمرفقات بأمان.');
    });

    const errorSummary = document.querySelector('[data-info-error-summary]');

    if (errorSummary) {
        window.setTimeout(() => errorSummary.focus(), 50);
    }

    window.addEventListener('beforeunload', (event) => {
        if (!hasUnsavedChanges || form.dataset.submitting === '1') {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });

    filterGovernorates();
    updateLivePreviews();
    updateCollectionSummaries();
    showStep(currentStep, false);
}

function initializeReferenceCopy() {
    const button = document.querySelector('[data-info-copy-reference]');
    const reference = document.querySelector('[data-info-reference]');
    const status = document.querySelector('[data-info-copy-status]');

    if (!button || !reference) {
        return;
    }

    async function copyReference() {
        const value = reference.textContent.trim();

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(value);
            } else {
                const temporaryInput = document.createElement('textarea');
                temporaryInput.value = value;
                temporaryInput.setAttribute('readonly', '');
                temporaryInput.style.position = 'fixed';
                temporaryInput.style.opacity = '0';
                document.body.appendChild(temporaryInput);
                temporaryInput.select();
                document.execCommand('copy');
                temporaryInput.remove();
            }

            if (status) {
                status.textContent = 'تم نسخ الرقم المرجعي';
            }

            const label = button.querySelector('span');
            if (label) {
                label.textContent = 'تم النسخ';
            }
        } catch {
            if (status) {
                status.textContent = 'تعذر النسخ تلقائياً. حدّد الرقم وانسخه يدوياً.';
            }
        }
    }

    button.addEventListener('click', copyReference);
}

function bootInformationPortal() {
    initializeInformationPortal();
    initializeReferenceCopy();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootInformationPortal);
} else {
    bootInformationPortal();
}
