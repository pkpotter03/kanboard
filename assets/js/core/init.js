document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing voice commands...');
    if (typeof KB !== 'undefined') {
        KB.on('dom.ready', function() {
            console.log('KB ready, rendering components...');
            KB.render();
        });
    } else {
        console.error('KB object not found!');
    }
}); 