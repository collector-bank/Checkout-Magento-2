define([
], function () {
    'use strict';

    function suspend() {
        if (typeof window.collector.suspendCount == 'undefined') {
            window.collector.suspendCount = 0;
        }
        window.collector.suspendCount++;
        if (window.collector.suspendCount == 1) {
            window.collector.checkout.api.suspend();
        }
    };
    function resume() {
        if (typeof window.collector.suspendCount == 'undefined') {
            window.collector.suspendCount = 1;
        }
        window.collector.suspendCount--;
        if (window.collector.suspendCount <= 0) {
            window.collector.checkout.api.resume();
            window.collector.suspendCount = 0;
        }
    };

    return {
        suspend: suspend,
        resume: resume,
    };
});