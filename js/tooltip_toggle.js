$(document).ready(function(){
    $('[data-toggle="tooltip"]').tooltip({
        placement: 'top',
        delay: {
            show: 500,
            hide: 100
        },
    }); 
    $('[data-toggle="tooltip"]').click(function () {
        
        setTimeout(function () {
            $('.tooltip').fadeOut('slow');
        }, 2000);

    });
});
