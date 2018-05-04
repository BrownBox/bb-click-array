jQuery(document).ready(function() {
	jQuery('.gform_bb.gfield_click_array div.s-html-wrapper').on('click', function() {
		jQuery(this).siblings('.gform_bb.gfield_click_array div.s-html-wrapper').removeClass('s-active').addClass('s-passive');
		jQuery(this).removeClass('s-passive').addClass('s-active');
		
		jQuery(this).siblings('input[type="text"]').val(jQuery(this).attr('data-clickarray-value')).trigger('change');
		jQuery(this).siblings('input[type="hidden"]').val(jQuery(this).attr('data-choice-id'));
	});
	
	jQuery('.ginput_click_array_other').on('change', function() {
	    var currency = new Currency(gf_global.gf_currency_config);
		jQuery(this).siblings('input[type="hidden"]').val('');
		var userValue = jQuery(this).val();
		jQuery(this).val(currency.toMoney(userValue).replace(".00", ""));
		jQuery(this).siblings('.gform_bb.gfield_click_array div.s-html-wrapper').each(function() {
			var thisValue = jQuery(this).attr('data-clickarray-value');
			if (thisValue == userValue || (jQuery(this).hasClass('s-currency') && currency.toNumber(thisValue) == currency.toNumber(userValue))) {
				jQuery(this).removeClass('s-passive').addClass('s-active');
			} else {
				jQuery(this).removeClass('s-active').addClass('s-passive');
			}
		});
	});
	jQuery('.s-html-value').each(function(el) {
		jQuery(this).html(jQuery(this).html().replace(".00", "")).show();
	});
});

gform.addFilter('gform_product_total', function(total, formId) {
    var currency = new Currency(gf_global.gf_currency_config);
	jQuery('.gfield_price .ginput_click_array_other').each(function() {
	    if (currency.toNumber(jQuery(this).val()) !== false) {
	        total += currency.toNumber(jQuery(this).val());
        }
	});
    return total;
});