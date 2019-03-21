

function check4block() {
    
    if(document.cookie.indexOf('notified') > 0){
        return;
    }
    
    if((typeof(noadblock) == 'undefined') ) {
        alert('Oops! It looks like you have an ad blocker on. Adblocker prevents submitting your donation. Please turn off the ad blocker and refresh to continue your checkout process.');
        document.cookie = "xnotified=yes";
    }
}

// disable adBlocker check and alert
// setTimeout(check4block, 2000);