define([
], function () {
    'use strict';

    function suspend() {
        window.collector.checkout.api.suspend();
    };
    function resume() {
        window.collector.checkout.api.resume();
    };

    return {
        suspend: suspend,
        resume: resume,
    };
});
