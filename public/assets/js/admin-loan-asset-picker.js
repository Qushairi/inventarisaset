(function () {
    'use strict';

    const parseJson = function (element, fallback) {
        if (!element) {
            return fallback;
        }

        try {
            return JSON.parse(element.textContent || '');
        } catch (error) {
            return fallback;
        }
    };

    const createElement = function (tagName, className, textContent) {
        const element = document.createElement(tagName);

        if (className) {
            element.className = className;
        }

        if (textContent !== undefined) {
            element.textContent = textContent;
        }

        return element;
    };

    const initializePicker = function (picker) {
        if (picker.dataset.initialized === 'true') {
            return;
        }

        const form = picker.closest('form');
        const searchSelect = picker.querySelector('[data-admin-loan-asset-search]');
        const assetsElement = picker.querySelector('[data-admin-loan-assets]');
        const selectedElement = picker.querySelector('[data-admin-loan-selected-items]');
        const itemsElement = picker.querySelector('[data-admin-loan-items]');
        const emptyElement = picker.querySelector('[data-admin-loan-empty]');
        const countElement = picker.querySelector('[data-admin-loan-item-count]');
        const errorElement = picker.querySelector('[data-admin-loan-picker-error]');
        const maxItems = Number.parseInt(picker.dataset.maxItems || '50', 10);

        if (!form || !searchSelect || !itemsElement || !emptyElement || !countElement || !errorElement) {
            return;
        }

        const assetOptions = parseJson(assetsElement, []);
        const initialItems = parseJson(selectedElement, []);
        const assets = new Map(assetOptions.map(function (asset) {
            return [String(asset.id), asset];
        }));
        const selectedItems = new Map();
        let choices = null;

        const setError = function (message) {
            const hasError = Boolean(message);

            errorElement.textContent = message || '';
            errorElement.classList.toggle('d-none', !hasError);
            errorElement.classList.toggle('d-block', hasError);
            picker.classList.toggle('has-error', hasError);
        };

        const rebuildSearch = function () {
            if (choices) {
                choices.destroy();
                choices = null;
            }

            searchSelect.replaceChildren();

            const availableAssets = assetOptions.filter(function (asset) {
                return !selectedItems.has(String(asset.id));
            });
            const placeholder = document.createElement('option');

            placeholder.value = '';
            placeholder.textContent = availableAssets.length > 0
                ? 'Cari nama atau kode aset'
                : 'Semua aset tersedia sudah dipilih';
            placeholder.selected = true;
            searchSelect.appendChild(placeholder);

            availableAssets.forEach(function (asset) {
                const option = document.createElement('option');
                const details = [asset.category, asset.location].filter(Boolean).join(' - ');

                option.value = String(asset.id);
                option.textContent = asset.label + (details ? ' - ' + details : '');
                searchSelect.appendChild(option);
            });

            if (window.Choices) {
                choices = new window.Choices(searchSelect, {
                    allowHTML: false,
                    itemSelectText: '',
                    noChoicesText: 'Semua aset tersedia sudah dipilih',
                    noResultsText: 'Aset tidak ditemukan',
                    placeholder: true,
                    placeholderValue: 'Cari nama atau kode aset',
                    searchEnabled: true,
                    searchFields: ['label'],
                    searchPlaceholderValue: 'Cari nama atau kode aset',
                    searchResultLimit: 12,
                    shouldSort: false,
                });
            }
        };

        const renderItems = function () {
            itemsElement.replaceChildren();

            Array.from(selectedItems.values()).forEach(function (item, index) {
                const asset = item.asset;
                const row = createElement('div', 'loan-asset-selected-row');
                const identity = createElement('div', 'loan-asset-selected-identity');
                const title = createElement('strong', null, asset.name);
                const metaParts = [asset.code, asset.category, asset.location].filter(Boolean);
                const meta = createElement('small', 'text-muted', metaParts.join(' - '));
                const stock = createElement('span', 'badge bg-light-success', 'Stok ' + asset.stock);
                const quantityField = createElement('div', 'loan-asset-selected-quantity');
                const quantityLabel = createElement('label', null, 'Jumlah');
                const quantityInput = document.createElement('input');
                const assetInput = document.createElement('input');
                const removeButton = createElement('button', 'btn btn-sm btn-light-danger icon', '');
                const removeIcon = createElement('i', 'bi bi-trash');

                identity.append(title, meta, stock);

                quantityLabel.htmlFor = 'admin_loan_item_quantity_' + index;
                quantityInput.type = 'number';
                quantityInput.id = quantityLabel.htmlFor;
                quantityInput.name = 'items[' + index + '][quantity]';
                quantityInput.className = 'form-control';
                quantityInput.min = '1';
                quantityInput.max = String(asset.stock);
                quantityInput.step = '1';
                quantityInput.value = String(item.quantity);
                quantityInput.required = true;
                quantityInput.addEventListener('input', function () {
                    item.quantity = quantityInput.value;
                });
                quantityField.append(quantityLabel, quantityInput);

                assetInput.type = 'hidden';
                assetInput.name = 'items[' + index + '][asset_id]';
                assetInput.value = String(asset.id);

                removeButton.type = 'button';
                removeButton.title = 'Hapus aset';
                removeButton.setAttribute('aria-label', 'Hapus ' + asset.name);
                removeButton.appendChild(removeIcon);
                removeButton.addEventListener('click', function () {
                    selectedItems.delete(String(asset.id));
                    setError('');
                    renderItems();
                });

                row.append(identity, quantityField, assetInput, removeButton);
                itemsElement.appendChild(row);
            });

            const itemCount = selectedItems.size;

            emptyElement.classList.toggle('d-none', itemCount > 0);
            countElement.textContent = itemCount + ' aset dipilih';
            picker.dataset.selectedCount = String(itemCount);
            rebuildSearch();
        };

        initialItems.forEach(function (item) {
            const asset = assets.get(String(item.asset_id));

            if (!asset || selectedItems.has(String(asset.id))) {
                return;
            }

            selectedItems.set(String(asset.id), {
                asset: asset,
                quantity: item.quantity || 1,
            });
        });

        searchSelect.addEventListener('change', function () {
            const asset = assets.get(String(searchSelect.value));

            if (!asset || selectedItems.has(String(asset.id))) {
                return;
            }

            if (selectedItems.size >= maxItems) {
                setError('Maksimal ' + maxItems + ' aset dalam satu peminjaman.');
                return;
            }

            selectedItems.set(String(asset.id), {
                asset: asset,
                quantity: 1,
            });
            setError('');
            renderItems();
        });

        form.addEventListener('submit', function (event) {
            if (selectedItems.size > 0) {
                setError('');
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();
            setError('Pilih minimal satu aset untuk dipinjam.');

            const choicesInput = picker.querySelector('.choices__input');

            if (choicesInput) {
                choicesInput.focus();
            } else {
                searchSelect.focus();
            }
        }, true);

        picker.dataset.initialized = 'true';
        renderItems();
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-admin-loan-asset-picker]').forEach(initializePicker);
    });
})();
