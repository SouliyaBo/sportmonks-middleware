/**
 * SportMonks Middleware Connector - JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Auto-refresh live scores every 60 seconds
        var liveScoresContainer = $('.smm-live-scores');
        
        if (liveScoresContainer.length > 0) {
            setInterval(function() {
                // You can implement auto-refresh here if needed
                console.log('Auto-refresh live scores');
            }, 60000); // 60 seconds
        }
        
        // Add animations
        $('.smm-match-card, .smm-fixture-card, .smm-standing-row').each(function(index) {
            $(this).css({
                'animation': 'fadeIn 0.5s ease-in-out ' + (index * 0.1) + 's forwards',
                'opacity': '0'
            });
        });
    });
    
})(jQuery);

// CSS Animation
var style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
