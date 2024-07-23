jQuery(document).ready(function ($) {
    function updateDeliveryDateOptions(variation) {
        if (variation._delivery_date_period && variation._delivery_date_day) {
            var dates = calculateRecurringDates(variation._delivery_date_period, variation._delivery_date_day);
            var $select = jQuery('#delivery_date');
            $select.empty();
                $select.append('<option value="">- Please Select -</option>');
            dates.forEach(function (date) {
                $select.append('<option value="' + date + '">' + date + '</option>');
            });
            jQuery('.delivery-date-options').show();
        } else {
            jQuery('.delivery-date-options').hide();
        }
    }

    function calculateRecurringDates(period, day) {
        var dates = [];
        var currentDate = new Date();

        for (var i = 0; i < 3; i++) {
            var nextDate = new Date(currentDate);
            switch (period) {
                case 'weekly':
                    nextDate.setDate(currentDate.getDate() + (i * 7) + (day - currentDate.getDay()));
                    break;
                case 'bi-weekly':
                    nextDate.setDate(currentDate.getDate() + (i * 14) + (day - currentDate.getDay()));
                    break;
                case 'monthly':
                    nextDate.setMonth(currentDate.getMonth() + i);
                    nextDate.setDate(day);
                    break;
            }
            dates.push(nextDate.toISOString().split('T')[0]);
        }

        return dates;
    }

    jQuery(document).on('show_variation', function (event, variation) {
        updateDeliveryDateOptions(variation);
    });

    // Validate the delivery date field & check if the product is subscription
    if (jQuery('body').hasClass('product-type-subscription') || jQuery('body').hasClass('product-type-variable-subscription') ) {
        jQuery('form.cart').on('submit', function () {
            var deliveryDate = jQuery('#delivery_date').val();
            if (!deliveryDate) {
                alert('Please select delivery date!');
                return false;
            }
        });
    }
});

//Inject Select Option to Cart & Checkout Page for Simple Subscription & Variable Subscription Products Only
document.addEventListener('DOMContentLoaded', function() {
    // Create the select element
    var select = document.createElement('select');
    select.className = 'delivery-date';

    // Function to append select to the target element
    function appendSelect() {
        var targetElement = document.querySelector('.wc-block-components-product-details__select-delivery-date');
        if (targetElement) {
            // Select the <select> element
            var $select = jQuery('.wc-block-components-product-details__select-delivery-date');

            // Create a new <form> element
            var $form = jQuery('<form></form>');

            // Wrap the <select> element in the <form>
            $select.wrap($form);

            // Existing array of options
            var options = [];

            jQuery('.wc-block-components-product-details__item-id').hide();

            var dropdownValues = jQuery('.wc-block-components-product-details__select-delivery-date span.wc-block-components-product-details__value').text();

            // Split the string into an array
            var newValues = dropdownValues.split('|').filter(function(value) {
                return value.trim() !== "";
            });

            // Map the new values to the format used in options
            var newOptions = newValues.map(function(item) {
                var parts = item.split('/');
                return {
                    text: parts[0],
                    value: parts[0],
                };
            });

            // Add a default option
            newOptions.unshift({ text: 'Select Delivery Date', value: '', selected: true });

            // Replace the existing options array with the new options
            options = newOptions;

            // Create and append option elements
            options.forEach(function(optionData) {
                var option = document.createElement('option');
                option.text = optionData.text;
                option.value = optionData.value;
                select.add(option);
            });

            targetElement.appendChild(select);
            jQuery('.wc-block-components-product-details__select-delivery-date span.wc-block-components-product-details__value').remove();

            $select.on('change', function() {
                // Log the selected option's value
                var selectedDate = jQuery('.wc-block-components-product-details__select-delivery-date .delivery-date').val();
                var productId    = jQuery('.wc-block-components-product-details__item-id span.wc-block-components-product-details__value').text();
                var $statusElement = jQuery('.wc-block-components-product-details__delivery-date span.wc-block-components-product-details__value');
                var $selectElement = jQuery('.wc-block-components-product-details__select-delivery-date .delivery-date');
                // Show "Updating..." text
                $statusElement.text('Updating...');
                $selectElement.prop('disabled', true);

                // Prepare data for AJAX request
                var data = {
                    action: 'update_delivery_date',
                    product_id: productId,
                    delivery_date: selectedDate,
                };

                // Send the AJAX request
                jQuery.ajax({
                    url: wc_add_to_cart_params.ajax_url, // WooCommerce AJAX URL
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        console.log('Delivery date updated successfully:', response);
                        $statusElement.text(formatDate(selectedDate));
                    },
                    error: function(error) {
                        console.error('Error updating delivery date:', error);
                    },
                    complete: function() {
                        // Re-enable select after the request is complete
                        $selectElement.prop('disabled', false);
                    }
                });
            });

            observer.disconnect(); // Stop observing after appending
        }
    }

    // Set up MutationObserver
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                appendSelect();
            }
        });
    });

    // Observe the target element
    observer.observe(document.body, { childList: true, subtree: true });

    // Call appendSelect() immediately in case the element is already present
    appendSelect();
});

// Format the date in "DD-Month-YYYY" format
function formatDate(dateString) {
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June', 
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    const dateParts = dateString.split('-'); // Split the date string into parts
    const year = dateParts[0];
    const monthIndex = parseInt(dateParts[1], 10) - 1; // Months are zero-based
    const day = dateParts[2];

    return `${day}-${months[monthIndex]}-${year}`;
}