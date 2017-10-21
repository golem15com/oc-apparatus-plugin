(function (url) {
    // Create a new `Image` instance
    var image = new Image();

    image.onload = function () {
        // Inside here we already have the dimensions of the loaded image
        var style = [
            // Hacky way of forcing image's viewport using `font-size` and `line-height`
            'font-size: 1px;',
            'line-height: 118px;',

            // Hacky way of forcing a middle/center anchor point for the image
            'padding: ' + this.height * .5 + 'px ' + this.width * .5 + 'px;',

            // Set image dimensions
            'background-size: 241px 118px;',

            // Set image URL
            'background: url(' + url + ');'
        ].join(' ');

        console.log('%c', style);
    };

    // Actually loads the image
    image.src = url;
})('http://' + window.location.hostname + '/plugins/keios/apparatus/assets/img/keios2.png');

console.log("%cIf you are not sure what you are doing here, please leave.", "color: #0a6dca; font-family: sans-serif; font-size: 2.5em; font-weight: bolder; text-shadow: #074b8a 1px 1px;");
console.log('For help, please visit https://keios.eu/');