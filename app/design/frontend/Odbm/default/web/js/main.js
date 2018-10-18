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
			if ($(this).children('input').is(':checked')) {
				$(this).addClass('checked');
				$('.actions.hidden').removeClass('hidden');
			}
			if ($(this).children('input').attr('id') === "_recurring-yes") {
				$('.paypal').addClass('hidden');
			} else {
                $('.paypal').removeClass('hidden');
			}
			$(this).siblings('.radio--button').removeClass('checked');
		});


		
	});

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