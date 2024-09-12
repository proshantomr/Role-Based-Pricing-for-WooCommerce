;(function($){
$(document).ready(function ($){
    $('#add-new-role-btn').on('click', function(e) {
        e.preventDefault();
        $('#add-role-form').toggle();
    });

    //input filed validations for role ID filed.
    $('#role_name').on('input', function() {
        const value = $(this).val();
        const isValid = /^[a-zA-Z_]+$/.test(value);
        if (!isValid) {
            $('#role_name_error').show();
        } else {
            $('#role_name_error').hide();
        }
    });
    $('form').on('submit', function(e) {
        const value = $('#role_name').val();
        const isValid = /^[a-zA-Z_]+$/.test(value);
        if (!isValid) {
            e.preventDefault();
            $('#role_name_error').show();
        }
    });

    // select-2.
    $('.select2').select2({
        placeholder: 'Choose your option',
        allowClear: true,
        width: '100 %'
    });

    // Handle "Apply Discount To" radio buttons
    if ($('input[name="rbpfw_apply_to"]:checked').val() === 'category') {
        $('#category-dropdown').show();
        $('#product-dropdown').hide();
    } else if ($('input[name="rbpfw_apply_to"]:checked').val() === 'product') {
        $('#category-dropdown').hide();
        $('#product-dropdown').show();
    }

    // Handle changes to the "Apply Discount To" radio buttons
    $('input[name="rbpfw_apply_to"]').on('change', function() {
        if ($(this).val() === 'category') {
            $('#category-dropdown').show();
            $('#product-dropdown').hide();
        } else if ($(this).val() === 'product') {
            $('#category-dropdown').hide();
            $('#product-dropdown').show();
        }
    });
});
})(jQuery);
