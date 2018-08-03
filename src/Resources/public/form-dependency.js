$(document).ready(function() {
    $('*:data(source)').each(function() {
        if ($(this).data('type') === 'checkbox') {
            console.log('here');
        }
    });
});