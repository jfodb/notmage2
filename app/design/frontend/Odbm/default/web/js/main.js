require(['jquery', 'jquery/ui'], function($) {
	 	$('.overlay').appendTo('body');

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

		function closeMenu() {
			$('.page-header .panel.wrapper, .overlay, .submenu, .has-submenu').removeClass('active');
			$('.overlay').removeClass('has-submenu');
			$('.submenu-arrow').removeClass('icon-chevron_right');
			$('.submenu-arrow').addClass('icon-chevron_left');
		}

		$('.radio--button').click(function(){
			//no value set yet
			if( (typeof $('#amount').valid === 'function') && !$('#amount').valid()) {
				$('#amount').focus();
				return false;
			} else {
				amount = $('#amount').val();
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

				if ( document.getElementById('_recurring-yes').checked ) {
					$('.box-tocart').hide();

					window.location.href = 'https://secure.ourdailybread.org/donation/?factor=' + sku + '&amount=' + amount +'&donation-options=monthly';

					e.preventDefault();
					return false;
				}
			} else {
				$('.paypal').removeClass('hidden');
				$('.dntpmtoptbx').removeClass('recurring');
			}
			$(this).siblings('.radio--button').removeClass('checked');
		});



	});



function check4block() {
    if((typeof(noadblock) == 'undefined') ) {
        alert('Oops! It looks like you have an ad blocker on. Adblocker prevents submitting your donation. Please turn off the ad blocker and refresh to continue your checkout process.')
    }
}

setTimeout(check4block, 2000);

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