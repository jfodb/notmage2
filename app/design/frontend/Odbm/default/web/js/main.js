require(['jquery', 'jquery/ui'], function($) {
	$(document).ready( function() {

		$('.overlay').appendTo('body');

		$('.description-toggle').click(function(e) {
			e.preventDefault();

			$(this).closest('.description-box').find('.description-inner').toggleClass('hidden');
		});

		$('.box-tocart').appendTo('#product-options-wrapper');

		$('.nav-toggle').click( function(e) {
			$('html').removeClass('nav-before-open nav-open');
			$('.page-header .panel.wrapper, .overlay').toggleClass('active');

			if ( $('.page-header .panel.wrapper').hasClass('active') ) {
				$('.overlay').click(closeMenu);
			} else {
				closeMenu();
			}

			e.preventDefault();
		});

		$('.has-submenu a').click( function(e) {
			var $submenuLi = $(this).closest('.has-submenu');
			$submenuLi.find( '.submenu' ).toggleClass('active');

			$submenuLi.toggleClass('active');
			$submenuLi.find( '.submenu-arrow' ).toggleClass('icon-chevron_left icon-chevron_right')

			if ( $submenuLi.find( '.submenu' ).hasClass('active') ) {
				$('.overlay').addClass('has-submenu');
			} else {
				$('.overlay').removeClass('has-submenu');
			}

			e.preventDefault();
		});

		// Hide Header on on scroll down
		var didScroll;
		var lastScrollTop = 0;
		var delta = 5;
		var navbarHeight = $('header').outerHeight();

		$(window).scroll(function(event){
			didScroll = true;
		});

		setInterval(function() {
			if (didScroll) {
				hasScrolled();
				didScroll = false;
			}
		}, 250);

		function hasScrolled() {
		    var scrollTop = $(this).scrollTop();

		    // Make sure they scroll more than delta
		    if( Math.abs(lastScrollTop - scrollTop) <= delta )
		     	return;

		    if ( scrollTop > lastScrollTop && scrollTop > navbarHeight ){
		        // Scroll Down
		        $('header').removeClass('nav-down').addClass('nav-up');
		    } else {
		        // Scroll Up
		        if( scrollTop + $(window).height() < $(document).height() ) {
		           $('header').removeClass('nav-up').addClass('nav-down');
		        }
		    }

		    lastScrollTop = scrollTop;
		}

		//If donation is one time only, then show payment methods immediately
		if($('.oneTimeOnly').length){
			$('.box-tocart').show();
			$('.dntpmtoptbx').removeClass('hidden');
		}


		function closeMenu() {
			$('.page-header .panel.wrapper, .overlay, .submenu, .has-submenu').removeClass('active');
			$('.overlay').removeClass('has-submenu');
			$('.submenu-arrow').removeClass('icon-chevron_right');
			$('.submenu-arrow').addClass('icon-chevron_left');
		}

		$('.radio--button').click(function(){
			//cleanup
			amount = $('#amount').val();
			tmpvar = filter_money_amount(amount);
			if(tmpvar != amount)
				$('#amount').val(tmpvar);
			amount = tmpvar;


			//no value set yet
			if( (typeof $('#amount').valid === 'function') && !$('#amount').valid()) {
				$('#amount').focus();
				return false;
			}

			$('.box-tocart').show();

			if ($(this).children('input').is(':checked')) {
				$(this).addClass('checked');
				$('.actions.hidden').removeClass('hidden');
			}
			if ($(this).children('input').attr('id') === "_recurring-yes") {
				$('.paypal').addClass('hidden');
				$('.dntpmtoptbx').addClass('recurring');

				var sku = document.getElementsByName('_motivation_code')[0].value;

				/*if ( document.getElementById('_recurring-yes').checked ) {
					$('.box-tocart').hide();

					window.location.href = 'https://secure.ourdailybread.org/donation/?factor=' + sku + '&amount=' + amount +'&donation-options=monthly';

					e.preventDefault();
					return false;
				}*/
			} else {
				$('.paypal').removeClass('hidden');
				$('.dntpmtoptbx').removeClass('recurring');
			}
			$(this).siblings('.radio--button').removeClass('checked');
		});


		$('input[name=_recurring]').change(function () {
			if($('#_recurring-yes').is(':checked')) {
				$('.ot').css('display', 'none');
				$('.rc').css('display','inline-block');
			} else {
				$('.ot').css('display', 'inline-block');
				$('.rc').css('display','none');
			}
		});


		setInterval(delivermessage, 300);


		fetchmessages();
	});
});

function fetchmessages() {

	require(
		['Magento_Customer/js/customer-data'],
		function(customerData) {
			let messages = customerData.get('messages')().messages;
			if(!messages || messages.length < 1){
				customerData.reload(['messages']);

				messages = customerData.get('messages')().messages;
			}

			if(messages && messages.length) {
				rendermessages(messages);
				customerData.invalidate(['messages']);

			}
	});
}

function delivermessage() {
	require([
		'Magento_Ui/js/modal/alert',
		'jquery', 'jquery/ui'
	], function (alert) { // Variable that represents the `alert` function

		if ((targetx = jQuery('input.usermessage').first()).val()) {
			if(pausemessages)
				return;
			pausemessages = true;
			alert({
				title: 'User Message:',
				content: targetx.val(),
				actions: {
					always: function () {
						targetx.remove();
						pausemessages = false;
					}
				}
			});

		}

	});
}

function rendermessages(messages) {

	for(var indx in messages) {
		msg = messages[indx];
		showmsg = document.createElement("input");
		showmsg.setAttribute("type", "hidden");
		showmsg.setAttribute("value", msg.text);
		showmsg.setAttribute("class", "usermessage");
		document.getElementById('maincontent').appendChild(showmsg);
	}

}

//make globally available
function trySendCheckoutGA() {
	if(typeof ga == 'function') {
		sendCheckoutGA();
	} else
		//the time is not right, wait to strike
		setTimeout(trySendCheckoutGA, 200);
}
function sendCheckoutGA() {
    if (document.getElementById('gadata')) {
        var gacat = jQuery('#gacat').val();
        var gaact = jQuery('#gaact').val();
        var galab = jQuery('#gacat').val() + ' - ' + jQuery('#galab').val();
        var gaval = jQuery('#gaval').val();

        ga('send', {
            hitType: 'event',
            eventCategory: gacat,
            eventAction: gaact,
            eventLabel: galab,
            eventValue: gaval
        });
    }
}

function filter_money_amount(amount) {
	//straight outta MOS
	if(typeof amount == 'string')
		num = amount.trim();
	else 
    	num = amount.toString();

    if( /^[0-9]+(\.[0-9][0-9])?$/.test(num)) {
        return num;
    }

    num = num.replace(/[jkl;>\/]/, '.');
    matches = /([0-9,\.]+)/.exec(num);

	//check for null
	if(matches) {
		newnum = matches[1];
		if(/,[0-9][0-9]$/.test(newnum)) {
			len = newnum.length;
			newnum = newnum.substr(0, len-3).replace('.', '') + '.' + newnum.substr(len-2, 2);
		}
		if(newnum.indexOf(',') > 0)
			newnum = newnum.replace(',', '');
		
			return newnum;
		
		}
	return amount;
}

//prevent fatal errors caused by adblocking faults
if(typeof require != 'undefined') {
    require.onError = function (e) {
        console.error("RequireJS Error", e);
    }
}

var pausemessages = false;