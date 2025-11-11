/**
 * Admin JavaScript for Default Featured Images Plugin
 */

(function($) {
    'use strict';

    const DFI = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.initAccordion();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Global fallback toggle
            $('#dfi-global-enabled').on('change', function() {
                $('.dfi-global-image-row').toggle(this.checked);
            });

            // Image selection
            $(document).on('click', '.dfi-select-image', function(e) {
                e.preventDefault();
                self.openMediaLibrary($(this));
            });

            // Remove image
            $(document).on('click', '.dfi-remove-image', function(e) {
                e.preventDefault();
                self.removeImage($(this));
            });

            // Clear fallback
            $(document).on('click', '.dfi-clear-fallback', function(e) {
                e.preventDefault();
                self.clearFallback($(this));
            });

            // Accordion controls
            $('.dfi-expand-all').on('click', function(e) {
                e.preventDefault();
                self.expandAll();
            });

            $('.dfi-collapse-all').on('click', function(e) {
                e.preventDefault();
                self.collapseAll();
            });

            // Refresh taxonomies
            $('.dfi-refresh-taxonomies').on('click', function(e) {
                e.preventDefault();
                self.refreshTaxonomies();
            });

            // Form submission
            $('#dfi-settings-form').on('submit', function(e) {
                e.preventDefault();
                self.saveSettings();
            });
        },

        /**
         * Initialize sortable
         */
        initSortable: function() {
            $('#dfi-taxonomy-priority').sortable({
                handle: '.dfi-drag-handle',
                placeholder: 'ui-state-highlight',
                axis: 'y',
                opacity: 0.7
            });
        },

        /**
         * Initialize accordion
         */
        initAccordion: function() {
            $('.dfi-accordion-header').on('click', function() {
                $(this).closest('.dfi-accordion-item').toggleClass('collapsed');
            });
        },

        /**
         * Expand all accordions
         */
        expandAll: function() {
            $('.dfi-accordion-item').removeClass('collapsed');
        },

        /**
         * Collapse all accordions
         */
        collapseAll: function() {
            $('.dfi-accordion-item').addClass('collapsed');
        },

        /**
         * Open media library
         */
        openMediaLibrary: function($button) {
            const $container = $button.closest('.dfi-image-selector');
            const $input = $container.find('input[type="hidden"]');
            const $preview = $container.find('.dfi-image-preview');

            const frame = wp.media({
                title: dfiAdmin.strings.selectImage,
                button: {
                    text: dfiAdmin.strings.useImage
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.id);
                
                const thumbUrl = attachment.sizes.thumbnail 
                    ? attachment.sizes.thumbnail.url 
                    : attachment.url;

                $preview.html(
                    '<img src="' + thumbUrl + '" alt="">' +
                    '<button type="button" class="dfi-remove-image" title="' + 
                    dfiAdmin.strings.removeImage + '">&times;</button>'
                );
            });

            frame.open();
        },

        /**
         * Remove image
         */
        removeImage: function($button) {
            const $container = $button.closest('.dfi-image-selector');
            const $input = $container.find('input[type="hidden"]');
            const $preview = $container.find('.dfi-image-preview');

            $input.val('');
            $preview.empty();
        },

        /**
         * Clear fallback
         */
        clearFallback: function($button) {
            if (!confirm(dfiAdmin.strings.confirmRemove)) {
                return;
            }

            const $row = $button.closest('tr');
            const $container = $row.find('.dfi-image-selector');
            const $input = $container.find('input[type="hidden"]');
            const $preview = $container.find('.dfi-image-preview');

            $input.val('');
            $preview.empty();
            $button.remove();

            this.showNotice(dfiAdmin.strings.saved, 'success');
        },

        /**
         * Refresh taxonomies
         */
        refreshTaxonomies: function() {
            const self = this;
            const $button = $('.dfi-refresh-taxonomies');

            $button.prop('disabled', true).addClass('updating-message');

            $.ajax({
                url: dfiAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dfi_get_taxonomies',
                    nonce: dfiAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateTaxonomyList(response.data.taxonomies);
                        self.showNotice('Taxonomies updated successfully!', 'success');
                    } else {
                        self.showNotice(response.data.message || dfiAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showNotice(dfiAdmin.strings.error, 'error');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('updating-message');
                }
            });
        },

        /**
         * Update taxonomy list
         */
        updateTaxonomyList: function(taxonomies) {
            const $list = $('#dfi-taxonomy-priority');
            const currentOrder = [];

            // Get current order
            $list.find('li').each(function() {
                currentOrder.push($(this).data('taxonomy'));
            });

            // Add new taxonomies that don't exist
            taxonomies.forEach(function(tax) {
                if (currentOrder.indexOf(tax.name) === -1) {
                    $list.append(
                        '<li data-taxonomy="' + tax.name + '">' +
                        '<span class="dashicons dashicons-menu dfi-drag-handle"></span>' +
                        '<span class="dfi-tax-label">' +
                        tax.label + ' <small>(' + tax.name + ')</small>' +
                        '</span>' +
                        '</li>'
                    );
                }
            });

            // Remove taxonomies that no longer exist
            const newTaxNames = taxonomies.map(t => t.name);
            $list.find('li').each(function() {
                const taxName = $(this).data('taxonomy');
                if (newTaxNames.indexOf(taxName) === -1) {
                    $(this).remove();
                }
            });
        },

        /**
         * Save settings
         */
        saveSettings: function() {
            const self = this;
            const $form = $('#dfi-settings-form');
            const $submitButton = $form.find('button[type="submit"]');

            // Get taxonomy priority order
            const taxonomyPriority = [];
            $('#dfi-taxonomy-priority li').each(function() {
                taxonomyPriority.push($(this).data('taxonomy'));
            });

            // Prepare form data
            const formData = $form.serializeArray();
            formData.push({
                name: 'action',
                value: 'dfi_save_settings'
            });
            formData.push({
                name: 'nonce',
                value: dfiAdmin.nonce
            });

            // Add taxonomy priority as array
            taxonomyPriority.forEach(function(tax) {
                formData.push({
                    name: 'taxonomy_priority[]',
                    value: tax
                });
            });

            $submitButton.prop('disabled', true).addClass('updating-message');

            $.ajax({
                url: dfiAdmin.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        self.showNotice(response.data.message, 'success');
                        
                        // Scroll to top
                        $('html, body').animate({ scrollTop: 0 }, 300);
                    } else {
                        self.showNotice(response.data.message || dfiAdmin.strings.error, 'error');
                    }
                },
                error: function() {
                    self.showNotice(dfiAdmin.strings.error, 'error');
                },
                complete: function() {
                    $submitButton.prop('disabled', false).removeClass('updating-message');
                }
            });
        },

        /**
         * Show notice
         */
        showNotice: function(message, type) {
            const $notice = $('.dfi-notice');
            
            $notice
                .removeClass('success error')
                .addClass(type)
                .html('<p>' + message + '</p>')
                .slideDown();

            setTimeout(function() {
                $notice.slideUp();
            }, 5000);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        DFI.init();
    });

})(jQuery);
